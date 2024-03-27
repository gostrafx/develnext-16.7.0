package org.develnext.jphp.genapi.template;

import org.develnext.jphp.genapi.description.*;
import org.develnext.jphp.genapi.parameter.BaseParameter;
import org.develnext.jphp.genapi.parameter.MethodReturnParameter;
import php.runtime.common.StringUtils;

import java.io.*;
import java.util.*;

public class SphinxTemplate extends BaseTemplate {

    protected Map<String, List<ClassDescription>> classes = new LinkedHashMap<String, List<ClassDescription>>();

    public SphinxTemplate(String language, String languageName) {
        super(language, languageName);
    }

    protected void echoType(String type) {
        sb.append(type.replaceAll("\\\\", "\\\\\\\\"));
    }

    protected void echoTypes(String... types) {
        int i = 0;
        if (types != null) {
            sb.append(":doc:`");
                for(String type : types) {
                    String ref = type;
                    if (ref.endsWith("[]"))
                        ref = ref.substring(0, ref.length() - 2);

                    if (i != 0)
                        sb.append(">`, :doc:`");

                    echoType(type);

                    if (BaseParameter.isNotClass(ref))
                        ref = ".types/" + ref;

                    sb.append(" </api_"+ language +"/").append(ref.replace('\\', '/'));
                    i++;
                }

            sb.append(">`");
        }
    }


    @Override
    protected void print(ClassDescription description) {
        sb.append(".. php:class:: ");
        echoType(description.getName());

        String extend = description.getExtends();
        String[] implement = description.getImplements();

        sb.append("\n");
        if (extend != null || implement != null || description.isAbstract() || description.isFinal()) {
            sb.append("\n");
            if (description.isAbstract()) {
                sb.append(" **abstract** class\n\n");
            }

            if (description.isFinal()) {
                sb.append(" **final** class\n\n");
            }

            if (description.isInterface()) {
                sb.append(" **interface**\n\n");
            }

            if (description.isTrait()) {
                sb.append(" **trait**\n\n");
            }

            if (extend != null) {
                sb.append(" **extends**: ");
                echoTypes(extend);
                sb.append("\n\n");
            }

            if (implement != null) {
                if (description.isInterface()) {
                    sb.append(" **extends**: ");
                } else {
                    sb.append(" **implements**: ");
                }
                echoTypes(implement);
                sb.append("\n\n");
            }

            if (!description.getChildClasses().isEmpty()) {
                sb.append("**Children**").append("\n\n----------------------\n\n");

                List<ClassDescription> tmp = new ArrayList<ClassDescription>(description.getChildClasses());

                Collections.sort(tmp, new Comparator<ClassDescription>() {
                    protected int getScore(ClassDescription e) {
                        int i = 0;
                        if (e.isAbstract())
                            i += 1000;
                        if (e.isFinal())
                            i += 500;

                        return i;
                    }

                    @Override
                    public int compare(ClassDescription o1, ClassDescription o2) {
                        int sc1 = getScore(o1);
                        int sc2 = getScore(o2);
                        if (sc1 == sc2) {
                            return o1.getName().compareTo(o2.getName());
                        } else {
                            return sc1 > sc2 ? -1 : 1;
                        }
                    }
                });

                for(ClassDescription e : tmp) {
                    sb.append(" * ");
                    if (e.isAbstract())
                        sb.append("**abstract** ");
                    if (e.isFinal())
                        sb.append("**final** ");

                    if (e.isInterface())
                        sb.append("**interface** ");
                    else
                        sb.append("**class** ");

                    echoTypes(e.getName());
                    sb.append("\n");
                }
            }
        }

        if (description.getDescription() != null) {
            sb.append("\n ");
            sb.append(addTabToDescription(description.getDescription(), 1));
            sb.append("\n");
        }

        sb.append("\n");
    }

    @Override
    protected void onBeforeClass(ClassDescription description) {
        String[] sections = StringUtils.split(description.getName(), "\\");

        String namespace = sections.length == 1
                ? ""
                : StringUtils.join(Arrays.copyOf(sections, sections.length - 1), "\\");

        List<ClassDescription> list = classes.get(namespace);
        if (list == null) {
            classes.put(namespace, list = new ArrayList<ClassDescription>());
        }
        list.add(description);

        echoType(description.getShortName());
        sb.append("\n")
                .append(StringUtils.repeat('-', description.getName().length()))
                .append("\n\n");

        String descFile = "/api_" + language + ".desc/" + description.getName().replace('\\', '/') + ".header.rst";
        sb.append(".. include:: ").append(descFile).append("\n\n");
    }

    @Override
    protected void onAfterClassBody(ClassDescription desc) {
        String descFile = "/api_" + language + ".desc/" + desc.getName().replace('\\', '/') + ".footer.rst";
        sb.append("\n\n.. include:: ").append(descFile).append("\n\n");
    }

    @Override
    protected void print(MethodDescription description) {
        print((FunctionDescription)description);
    }

    protected String addTabToDescription(String description, int tabCount) {
        description = getDescription(description, language);

        StringBuilder builder = new StringBuilder();
        BufferedReader reader = new BufferedReader(new StringReader(description));

        String line;
        int i = 0;
        try {
            while ((line = reader.readLine()) != null){
                if (i != 0){
                    line = StringUtils.repeat(' ', tabCount) + line;
                    builder.append("\n");
                }
                builder.append(line);
                i++;
            }
        } catch (IOException e) {
            throw new RuntimeException(e);
        }
        return builder.toString();
    }

    @Override
    protected void onBeforeMethods(ClassDescription desc) {
        sb.append("\n\n").append("**Methods**").append("\n\n----------\n\n");
    }

    @Override
    protected void onBeforeProperties(ClassDescription desc) {
        sb.append("\n\n").append("**Properties**").append("\n\n----------\n\n");
    }

    @Override
    protected void onBeforeConstants(ClassDescription desc) {
        sb.append("\n\n").append("**Constants**").append("\n\n----------\n\n");
    }

    @Override
    protected void print(ConstantDescription description) {
        sb.append(" .. php:const:: ").append(description.getName()).append("\n\n");

        if (description.getDescription() != null && !description.getDescription().isEmpty()) {
            sb.append("  ")
                    .append(addTabToDescription(description.getDescription().trim(), 2))
                    .append("\n\n");
        }
    }

    @Override
    protected void print(PropertyDescription desc) {
        sb.append(" .. php:attr:: ").append(desc.getName()).append("\n");

        if (desc.getTypes() != null && desc.getTypes().length > 0) {
            sb.append("\n");
            sb.append("  "); echoTypes(desc.getTypes());
            sb.append("\n");
        }

        sb.append("\n");

        boolean add = false;
        if (desc.isStatic()) {
            sb.append("  **static**\n\n");
            add = true;
        }

        if (desc.isPrivate()) {
            sb.append("  **private**\n\n");
            add = true;
        }

        if (desc.isProtected()) {
            sb.append("  **protected**\n\n");
            add = true;
        }

        if (desc.isReadonly()) {
            sb.append("  **read-only**\n\n");
            add = true;
        }

        if (add)
            sb.append("\n");

        if (desc.getDescription() != null && !desc.getDescription().isEmpty()) {
            sb.append("  ")
                    .append(addTabToDescription(desc.getDescription().trim(), 2))
                    .append("\n\n");
        }
    }

    @Override
    protected void print(FunctionDescription description) {
        if (description instanceof MethodDescription && ((MethodDescription) description).isStatic()) {
            sb.append(" .. php:staticmethod:: ");
        } else {
            sb.append(" .. php:method:: ");
        }

        sb.append(description.getName()).append("(");
        int i = 0;

        Collection<ArgumentDescription> args = description.getArguments();
        for (ArgumentDescription arg : args) {
            if (i != 0)
                sb.append(", ");

            sb.append("$").append(arg.getName());
            if (arg.getValue() != null) {
                sb.append(" = ").append(arg.getValue());
            }

            i++;
        }

        sb.append(")\n\n");

        if (description instanceof MethodDescription) {
            MethodDescription meth = (MethodDescription)description;
            boolean add = false;
            if (meth.isFinal()) {
                sb.append("  **final**\n\n");
                add = true;
            }

            if (meth.isAbstract()) {
                sb.append("  **abstract**\n\n");
                add = true;
            }

            if (meth.isPrivate()) {
                sb.append("  **private**\n\n");
                add = true;
            }

            if (meth.isProtected()) {
                sb.append("  **protected**\n\n");
                add = true;
            }

            if (add)
                sb.append("\n");
        }

        if (description.getDescription() != null && !description.getDescription().isEmpty()) {
            sb.append("  ")
                    .append(addTabToDescription(description.getDescription().trim(), 2))
                    .append("\n\n");
        }

        if (description.getThrowsParameters() != null) {
            for(MethodReturnParameter e : description.getThrowsParameters()) {
                sb.append("  **throws** ");
                echoTypes(e.getTypes());
                if (e.getDescription() != null && !e.getDescription().trim().isEmpty()) {
                    sb.append(" ");
                    sb.append(addTabToDescription(e.getDescription().trim(), 2));
                }
                sb.append("\n\n");
            }
        }
    }

    @Override
    protected void print(ArgumentDescription description) {
        sb.append("  :param ");

        sb.append("$").append(description.getName()).append(": ");

        if (description.getTypes() != null) {
            echoTypes(description.getTypes());
            sb.append(" ");
        }

        if (description.getDescription() != null && !description.getDescription().trim().isEmpty()) {
            sb.append(" - ");
            sb.append(addTabToDescription(description.getDescription(), 2));
        }

        sb.append("\n");
    }

    @Override
    protected void onAfterMethod(MethodDescription desc) {
        onAfterFunction(desc);
    }

    @Override
    protected void onAfterFunction(FunctionDescription desc) {
        if (desc.getReturnTypes() != null
                || (desc.getReturnDescription() != null && !desc.getReturnDescription().isEmpty())) {
            sb.append("  :returns: ");
            if (desc.getReturnTypes() != null) {
                echoTypes(desc.getReturnTypes());
                sb.append(" ");
            }

            if (desc.getReturnDescription() != null) {
                sb.append(addTabToDescription(desc.getReturnDescription().trim(), 2));
            }

            sb.append("\n");
        }

        sb.append("\n");
    }

    @Override
    public void onEnd(File targetDirectory) {
        onEnd(targetDirectory, null);
    }

    protected List<String> onEnd(File targetDirectory, String namespace) {
        StringBuilder sb = new StringBuilder();

        String sectionName;
        if (namespace != null) {
            String[] tmp = StringUtils.split(namespace, '\\');
            sectionName = tmp[tmp.length - 1];
        } else {
            sectionName = "API (" + languageName + ")";
        }

        sb.append(sectionName)
                .append("\n")
                .append(StringUtils.repeat('-', 30)).append("\n\n");


        sb.append("\n.. include:: /api_")
                .append(language)
                .append(".desc/");

        if (namespace != null) {
            sb.append(namespace.replace('\\', '/')).append("/");
        }
        sb.append("index.header.rst\n\n");


        sb.append(".. toctree::\n" +
                "   :maxdepth: 3\n\n");

        File[] files = targetDirectory.listFiles();

        List<String> ctree = new ArrayList<String>();
        if (files != null) {
            for(File dir : files) {
                if (dir.isFile() && dir.getName().endsWith(".rst")) {
                    if (!dir.getName().equals("index.rst")) {
                        ctree.add(dir.getName());
                    }
                } else if (dir.isDirectory() && !dir.getName().startsWith(".")) {
                    ctree.add(dir.getName() + "/index");
                }
            }

            for(File dir : files) {
                if (dir.isDirectory() && !dir.getName().startsWith(".")) {
                    List<String> tmp = onEnd(dir, namespace == null ? dir.getName() : namespace + "\\" + dir.getName());
                    if (ctree.isEmpty() || ctree.size() == 1) {
                        ctree.clear();
                        for(String e : tmp) {
                            ctree.add(dir.getName() + "/" + e);
                        }
                    }
                }
            }
        }

        for(String e : ctree) {
            sb.append("   ").append(e).append("\n");
        }

        sb.append("\n.. include:: /api_")
                .append(language)
                .append(".desc/");

        if (namespace != null) {
                sb.append(namespace.replace('\\', '/')).append("/");
        }
        sb.append("index.footer.rst\n\n");


        File file = new File(targetDirectory, "/index.rst");
        try {
            FileWriter fileWriter = new FileWriter(file, false);
            fileWriter.write(sb.toString());
            fileWriter.close();
        } catch (IOException e) {
            throw new RuntimeException(e);
        }

        return ctree;
    }
}
