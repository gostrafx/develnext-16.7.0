<?php
namespace ide\editors;

use develnext\lexer\inspector\entry\ExtendTypeEntry;
use develnext\lexer\inspector\entry\TypeEntry;
use develnext\lexer\inspector\entry\TypePropertyEntry;
use ide\editors\form\FormNamedBlock;
use ide\formats\GuiFormDumper;
use ide\formats\ScriptModuleFormat;
use ide\Ide;
use ide\Logger;
use ide\project\behaviours\GuiFrameworkProjectBehaviour;
use ide\project\ProjectIndexer;
use ide\scripts\AbstractScriptComponent;
use ide\scripts\ScriptComponentContainer;
use ide\scripts\UnknownScriptComponent;
use ide\utils\FileUtils;
use ide\utils\Json;
use php\format\JsonProcessor;
use php\gui\designer\UXDesignPane;
use php\gui\event\UXMouseEvent;
use php\gui\framework\AbstractScript;
use php\gui\layout\UXAnchorPane;
use php\gui\UXLabel;
use php\gui\UXNode;
use php\lib\fs;
use php\lib\Items;
use php\lib\reflect;
use php\lib\Str;
use stdClass;

/**
 * Class ScriptModuleEditor
 * @package ide\editors
 *
 * @property ScriptModuleFormat $format
 */
class ScriptModuleEditor extends FormEditor
{
    /**
     * @var JsonProcessor
     */
    private $json;

    /**
     * Json from $name.module file
     * @var array
     */
    protected $meta = [];

    /**
     * @var ScriptComponentContainer[]
     */
    protected $components = [];

    /**
     * @var string
     */
    protected $metaFile;

    /**
     * @var array
     */
    protected $properties;

    public function __construct($file)
    {
        $this->metaFile = fs::pathNoExt($file) . '.module';
        $this->properties = [];
        $this->json = new JsonProcessor(JsonProcessor::SERIALIZE_PRETTY_PRINT | JsonProcessor::DESERIALIZE_AS_ARRAYS);

        parent::__construct($file, new GuiFormDumper([]));

        $this->behaviourManager->setTargetGetter(function ($nodeId) {
            $container = $this->components[$nodeId];

            if ($container) {
                return $container->getType();
            }

            return null;
        });
    }

    public function isAppModule()
    {
        $name = fs::nameNoExt($this->file);

        return ($name == 'AppModule');
    }

    public function getIcon()
    {
        if ($this->isAppModule()) {
            return "icons/appBlock16.png";
        }

        return parent::getIcon();
    }

    public function __set($name, $value)
    {
        $this->properties[$name] = $value;
    }

    public function __get($name)
    {
        return $this->properties[$name];
    }

    protected function reindexImpl(ProjectIndexer $indexer)
    {
        $result = [];

        $indexer->remove($this->file, '_objects');

        $index = [];

        foreach ($this->components as $id => $component) {
            $index[$id] = [
                'id' => $id,
                'type' => reflect::typeOf($component->getType()),
            ];
        }

        $indexer->set($this->file, '_objects', $index);

        $this->refreshInspectorType();

        return $result;
    }

    public function refresh()
    {
        parent::refresh(); // TODO: Change the autogenerated stub
    }


    public function open()
    {
        parent::open();

        foreach ($this->components as $id => $component) {
            $needSelect = false;

            if ($component->getType() instanceof UnknownScriptComponent) {
                if ($this->getFormat()->getElementByClass($component->getType()->getType())) {
                    if ($component->getIdeNode()) {
                        if ($this->designer->isSelectedNode($component->getIdeNode())) {
                            $this->designer->unselectNode($component->getIdeNode());
                            $needSelect = true;
                        }

                        $this->designer->unregisterNode($component->getIdeNode());
                        $component->getIdeNode()->free();
                    }

                    $component = $this->loadComponent($id, $this->meta['components'][$id]);
                }
            }

            /** @var FormNamedBlock $node */
            $node = $component->getIdeNode();

            if ($node == null) {
                $this->addContainer($component);

                $node = $component->getIdeNode();
                $this->designer->registerNode($node);

                if ($needSelect) {
                    $this->designer->selectNode($node);
                }
            }

            $element = $this->format->getFormElement($node);

            if ($element && !($element instanceof UnknownScriptComponent)) {
                $node->setInvalid(false);
            } else {
                $node->setInvalid(true);
            }
        }
    }

    public function refreshInspectorType()
    {
        if ($project = Ide::project()) {
            $type = new TypeEntry();
            $type->fulledName = $className = "{$project->getPackageName()}\\modules\\" . $this->getTitle();

            foreach ($this->getModules() as $name) {
                $name = "mixin:{$project->getPackageName()}\\modules\\$name";
                $type->extends[str::lower($name)] = $e = new ExtendTypeEntry($name, ['weak' => true, 'public' => true]);
            }

            foreach ($this->getFormEditors() as $one) {
                $name = "mixin:{$project->getPackageName()}\\forms\\" . $one->getTitle();
                $type->extends[str::lower($name)] = $e = new ExtendTypeEntry($name, ['weak' => true, 'public' => true]);
            }

            foreach ($this->components as $id => $el) {
                $type->properties[$id] = $prop = new TypePropertyEntry();
                $prop->name = $id;

                $prop->data['content']['DEF'] = $el->getType()->getName();
                $prop->data['icon'] = $el->getType()->getIcon();
                $prop->data['type'][] = $el->getType()->getElementClass() ?: AbstractScript::class;
            }

            foreach ($project->getInspectors() as $inspector) {
                $inspector->putDynamicType($type);
            }

            $type = new TypeEntry();
            $type->fulledName = "mixin:{$project->getPackageName()}\\modules\\" . $this->getTitle();

            foreach ($this->getModules() as $name) {
                $name = "mixin:{$project->getPackageName()}\\modules\\$name";
                $type->extends[str::lower($name)] = $e = new ExtendTypeEntry($name, ['weak' => true, 'public' => true]);
            }

            foreach ($this->components as $id => $el) {
                $type->properties[$id] = $prop = new TypePropertyEntry();
                $prop->name = $id;

                $prop->data['icon'] = $el->getType()->getIcon();
                $prop->data['type'][] = $el->getType()->getElementClass() ?: AbstractScript::class;
            }

            foreach ($project->getInspectors() as $inspector) {
                $inspector->putDynamicType($type);
            }
        }
    }

    public function save()
    {
        $this->saveOthers();

        /** @var ScriptComponentContainer[] $components */
        $components = Items::sort($this->components, function (ScriptComponentContainer $a, ScriptComponentContainer $b) {
            $aScore = $a->getY() * 1000 + $a->getX();
            $bScore = $b->getY() * 1000 + $b->getY();

            if ($aScore == $bScore) {
                return 0;
            }

            return $aScore > $bScore ? -1 : 1;
        }, true);

        $meta = $this->meta;
        $meta['props'] = $this->properties;
        $meta['components'] = [];

        foreach ($components as $id => $component) {
            $cmpMeta = [
                'type' => $component->getType()->getType(),
                'x' => $component->getX(),
                'y' => $component->getY(),
                'props' => (array)$component->getProperties(),
            ];

            $meta['components'][$id] = $cmpMeta;
        }

        FileUtils::put(fs::pathNoExt($this->file) . '.module', $this->json->format($meta));
    }

    public function addContainer(ScriptComponentContainer $container)
    {
        $this->components[$container->id] = $container;

        /** @var FormNamedBlock $node */
        $node = $container->getType()->createElement();
        $node->setTitle($container->id);

        $container->setIdeNode($node);
        $node->userData = $container;

        $node->position = [$container->getX(), $container->getY()];

        $node->watch('layoutX', function () use ($container, $node) {
            $container->setX($node->x);
        });
        $node->watch('layoutY', function () use ($container, $node) {
            $container->setY($node->y);
        });

        $this->layout->add($node);
    }

    protected function updateEmptyLabel()
    {
        if ($this->components) {
            if ($label = $this->layout->lookup('#empty-title')) {
                $label->free();
            }
        } else {
            $label = new UXLabel('Добавьте сюда компоненты для модуля.');
            $label->classes->add('dn-title');
            $label->textColor = 'gray';
            $label->mouseTransparent = true;
            $label->id = 'empty-title';

            $this->layout->add($label);
        }
    }

    protected function loadComponent($id, array $cmpMeta)
    {
        $element = $this->getFormat()->getElementByClass($cmpMeta['type']);

        if ($element && $element instanceof AbstractScriptComponent) {
            $container = new ScriptComponentContainer($element, $id);
        } else {
            $container = new ScriptComponentContainer(new UnknownScriptComponent($cmpMeta['type']), $id);
        }

        $container->setX((int) $cmpMeta['x']);
        $container->setY((int) $cmpMeta['y']);

        foreach ((array) $cmpMeta['props'] as $key => $value) {
            $container->__set($key, $value);
        }

        return $this->components[$id] = $container;
    }

    public function load()
    {
        $this->loadOthers();

        $this->meta = ['props' => [], 'components' => []];

        $this->components = [];

        if (fs::isFile($this->metaFile)) {
            try {
                $this->meta = Json::fromFile($this->metaFile);

                if (!is_array($this->meta['props'])) {
                    $this->meta['props'] = [];
                }

                if (!is_array($this->meta['components'])) {
                    $this->meta['components'] = [];
                }

                $this->properties = $this->meta['props'];

                /** @var ScriptModuleFormat $format */
                $format = $this->getFormat();

                foreach ($this->meta['components'] as $id => $cmpMeta) {
                    $this->loadComponent($id, $cmpMeta);
                }
            } catch (\Exception $e) {
                $this->setIncorrectFormat(true);
                return false;
            }
        }


        $this->layout = new UXAnchorPane();
        //$this->layout->minSize = [800, 600];
        //$this->layout->size = [800, 600];
        //$this->layout->css('background-color', 'white');

        $this->updateEmptyLabel();
    }

    public function changeNodeId($container, $newId)
    {
        /** @var ScriptComponentContainer $container */
        if (!$this->checkNodeId($newId)) {
            return 'invalid';
        }

        if ($container && $container->id == $newId) {
            return '';
        }

        if (isset($this->components[$newId])) {
            return 'busy';
        }

        $oldId = $container->id;
        $container->id = $newId;

        $this->components[$newId] = $this->components[$oldId];
        unset($this->components[$oldId]);

        // other ...

        $this->behaviourManager->changeTargetId($oldId, $newId);
        $binds = $this->eventManager->renameBind($oldId, $newId);

        $container->getIdeNode()->setTitle($newId);

        foreach ($binds as $bind) {
            $this->actionEditor->renameMethod($bind['className'], $bind['methodName'], $bind['newMethodName']);
        }

        $this->codeEditor->loadContentToArea(false);
        $this->codeEditor->doChange(true);
        $this->reindex();

        $this->leftPaneUi->updateEventList($newId);
        $this->leftPaneUi->updateBehaviours($newId);
        $this->leftPaneUi->refreshObjectTreeList($newId);

        return '';
    }

    public function getNodeId($node)
    {
        /** @var ScriptComponentContainer $container */
        $container = $node->userData;

        if ($container instanceof ScriptComponentContainer) {
            return $container->id;
        }

        return null;
    }

    protected function makeActionsUi(UXDesignPane $designPane)
    {
        return null;
    }

    protected function makePrototypePane()
    {
        return null;
    }

    protected function makeDesigner($fullArea = true)
    {
        $pane = parent::makeDesigner(true);
        $this->designer->snapSizeX = $this->designer->snapSizeY = 16;
        return $pane;
    }

    public function deleteNode($node)
    {
        /** @var ScriptComponentContainer $container */
        $container = $node->userData;

        if (!($container instanceof ScriptComponentContainer)) {
            return;
        }

        unset($this->components[$container->id]);

        $designer = $this->designer;

        $designer->unselectNode($node);
        $designer->unregisterNode($node);

        $node->parent->remove($node);

        if ($container && $container->id) {
            $binds = $this->eventManager->findBinds($container->id);

            foreach ($binds as $bind) {
                $this->actionEditor->removeMethod($bind['className'], $bind['methodName']);
            }
        }

        if ($container && $container->id && $this->eventManager->removeBinds($container->id)) {
            $this->codeEditor->loadContentToArea(false);
            $this->codeEditor->doChange(true);
        }

        if ($container && $container->id) {
            $this->behaviourManager->removeBehaviours($container->id);
            $this->behaviourManager->save();
        }

        $this->leftPaneUi->refreshObjectTreeList();

        $this->reindex();

        $this->updateEmptyLabel();
    }

    public function getModules()
    {
        return [];
    }

    /**
     * @return ScriptComponentContainer[]
     */
    public function getComponents()
    {
        return $this->components;
    }

    public function getModuleName()
    {
        return fs::nameNoExt($this->file);
    }

    protected $forms = [];

    /**
     * @return FormEditor[]
     * @throws \Exception
     */
    public function getFormEditors()
    {
        $project = Ide::get()->getOpenedProject();

        if (!$project) {
            return [];
        }

        /** @var GuiFrameworkProjectBehaviour $gui */
        $gui = $project->getBehaviour(GuiFrameworkProjectBehaviour::class);

        $forms = $gui->getFormEditorsOfModule($this->getModuleName());

        return $forms;
    }

    /**
     * @param AbstractScriptComponent $element
     * @param $screenX
     * @param $screenY
     * @param null $parent
     * @return mixed|UXNode
     * @throws \php\lang\IllegalArgumentException
     */
    protected function createElement($element, $screenX, $screenY, $parent = null)
    {
        $selected = $element;

        $node = $selected->createElement();

        $container = new ScriptComponentContainer($selected, $this->makeId($selected->getIdPattern()));
        $container->setIdeNode($node);

        $node->setTitle($container->id);

        $node->userData = $container;

        $this->components[$container->id] = $container;

        $size = $node->size;

        $node->observer('layoutX')->addListener(function () use ($container, $node) {
            $container->setX($node->x);
        });

        $node->observer('layoutY')->addListener(function () use ($container, $node) {
            $container->setY($node->y);
        });

        $position = $this->layout->screenToLocal($screenX, $screenY);

        $snapSizeX = $this->designer->snapSizeX;
        $snapSizeY = $this->designer->snapSizeY;

        if ($this->designer->snapEnabled) {
            $size[0] = floor($size[0] / $snapSizeX) * $snapSizeX;
            $size[1] = floor($size[1] / $snapSizeY) * $snapSizeY;

            $position[0] = floor($position[0] / $snapSizeX) * $snapSizeX;
            $position[1] = floor($position[1] / $snapSizeY) * $snapSizeY;
        }

        $node->position = $position;

        $this->layout->add($node);
        $this->designer->registerNode($node);

        foreach ($selected->getInitProperties() as $key => $property) {
            $container->{$key} = $property['value'];
        }

        $this->designer->requestFocus();

        $this->reindex();
        $this->leftPaneUi->refreshObjectTreeList($this->getNodeId($node));
        $this->save();

        $this->updateEmptyLabel();

        waitAsync(100, function () {
            $this->designer->update();
        });

        return $node;
    }

    protected function ___onAreaMouseUp(UXMouseEvent $e)
    {
        $selected = $this->elementTypePane->getSelected();

        $this->save();

        /** @var AbstractScriptComponent $selected */
        if ($selected) {
            $node = $selected->createElement();

            $container = new ScriptComponentContainer($selected, $this->makeId($selected->getIdPattern()));
            $container->setIdeNode($node);

            $node->setTitle($container->id);

            $node->userData = $container;

            $this->components[$container->id] = $container;

            $size = $node->size;

            $node->watch('layoutX', function () use ($container, $node) {
                $container->setX($node->x);
            });
            $node->watch('layoutY', function () use ($container, $node) {
                $container->setY($node->y);
            });

            $position = [$e->x, $e->y];

            $snapSizeX = $this->designer->snapSizeX;
            $snapSizeY = $this->designer->snapSizeY;

            if ($this->designer->snapEnabled) {
                $size[0] = floor($size[0] / $snapSizeX) * $snapSizeX;
                $size[1] = floor($size[1] / $snapSizeY) * $snapSizeY;

                $position[0] = floor($position[0] / $snapSizeX) * $snapSizeX;
                $position[1] = floor($position[1] / $snapSizeY) * $snapSizeY;
            }

            $node->position = $position;

            $this->layout->add($node);
            $this->designer->registerNode($node);

            if (!$e->controlDown) {
                $this->elementTypePane->clearSelected();
            }

            foreach ($selected->getInitProperties() as $key => $property) {
                $container->{$key} = $property['value'];
            }

            $this->designer->requestFocus();

            $this->reindex();
            $this->leftPaneUi->refreshObjectTreeList($this->getNodeId($node));
            $this->save();
        } else {
            $this->updateProperties($this);
        }
    }

    public function makeId($idPattern)
    {
        $id = Str::format($idPattern, '');

        if ($this->components[$id]) {
            $id = Str::format($idPattern, 'Alt');

            if ($this->components[$id]) {
                $n = 3;

                do {
                    $id = Str::format($idPattern, $n++);
                } while ($this->components[$id]);
            }
        }

        return $id;
    }

    public function getRefactorRenameNodeType()
    {
        return ScriptModuleFormat::REFACTOR_ELEMENT_ID_TYPE;
    }

    public function delete($silent = false)
    {
        foreach ($this->getFormEditors() as $editor) {
            $editor->removeModule($this->getModuleName());
        }

        fs::delete("$this->metaFile");

        parent::delete($silent);
    }
}