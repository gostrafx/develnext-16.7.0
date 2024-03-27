<?php
namespace framework\web\ui;

/**
 * Class UIIcon
 * @package framework\web\ui
 *
 * @property string $kind
 * @property string $color
 * @property int $imageSize
 */
class UIIcon extends UINode
{
    /**
     * @var string
     */
    private $kind = '';

    /**
     * @var int
     */
    private $imageSize = 32;

    /**
     * @var string
     */
    private $color = 'black';

    /**
     * UIIcon constructor.
     * @param string $kind
     * @param int $imageSize
     * @param string $color
     */
    public function __construct(string $kind = '', $imageSize = 32, string $color = 'black')
    {
        parent::__construct();
        
        $this->kind = $kind;
        $this->imageSize = $imageSize;
        $this->color = $color;
    }


    /**
     * @return string
     */
    public function uiSchemaClassName(): string
    {
        return "Icon";
    }

    /**
     * @return string
     */
    protected function getKind(): string
    {
        return $this->kind;
    }

    /**
     * @param string $kind
     */
    protected function setKind(string $kind)
    {
        $this->kind = $kind;
    }

    /**
     * @return int
     */
    protected function getImageSize(): int
    {
        return $this->imageSize;
    }

    /**
     * @param int $imageSize
     */
    protected function setImageSize(int $imageSize)
    {
        $this->imageSize = $imageSize;
    }

    /**
     * @return string
     */
    protected function getColor(): string
    {
        return $this->color;
    }

    /**
     * @param string $color
     */
    protected function setColor(string $color)
    {
        $this->color = $color;
    }
}