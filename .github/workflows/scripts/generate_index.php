<?php

function compute_hash($data) {
    return $data ? sha1($data) : null;
}

function update_readme($categories) {
    $readme = fopen("README.md", "w");
    fwrite($readme,
        "# Wizard Templates\nEnhance the usability and functionality of ProcessMaker. These templates offer seamless integration with various tools by allowing users to declare their accounts. Users can easily select a template of their choice, eliminating the need to go through the modeler."
    );
    ksort($categories);  // Sort categories alphabetically
    foreach ($categories as $category => $templates) {
        $category = str_replace("-", " ", $category);
        $category = ucwords($category);
        fwrite($readme, "\n## $category\n");
        // Sort templates alphabetically within each category
        usort($templates, function($a, $b) { return strcmp($a['name'], $b['name']); });
        
        foreach ($templates as $template) {
            // print_r($template);
            // die();
            foreach ($template as $value) {
                $string = "- **[{$value['name']}](/{$value['relative_path']})**: {$value['description']}";
                if ($value['version']) {
                    $string .= " (Version {$value['version']})\n";
                } else {
                    $string .= "\n";
                }
                fwrite($readme, $string);
            }

        }
    }
    fclose($readme);
}

function main() {
    $root_dir = ".";
    $categories = [];

    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root_dir));

    foreach ($rii as $file) {
        if ($file->isDir()) continue;
        if (pathinfo($file->getPathname(), PATHINFO_EXTENSION) != "json") continue;
        if ($file->getFilename() == "index.json") continue;

        $filepath = $file->getPathname();
        $data = json_decode(file_get_contents($filepath), true);

        $categoryPath = str_replace("./", "", $file->getPath());

        $categorySegments = explode("/", $categoryPath);
        

        $version = null;
        if (isset($data["export"][$data["root"]]["attributes"])) {
            if (array_key_exists("version", $data["export"][$data["root"]]["attributes"])) {
                $version = $data["export"][$data["root"]]["attributes"]["version"];
            }
        }
        $mod_time = $data["export"][$data["root"]]["attributes"]["updated_at"];

        $template_info = [
            "name" => $data["name"],
            "description" => $data["export"][$data["root"]]["description"],
            "hash" => compute_hash($data["export"][$data["root"]]["attributes"]["manifest"]),
            "mod_time" => $mod_time,
            "relative_path" => $filepath,
            "uuid" => $data["root"],
            "version" => $version,
        ];

        $currentCategory = &$categories;
        foreach ($categorySegments as $segment) {
            if (!isset($currentCategory[$segment])) {
                $currentCategory[$segment] = [];
            }
            $currentCategory = &$currentCategory[$segment];
        }

        $currentCategory[] = $template_info;

    }


    ksort($categories);  // Sort categories alphabetically
    foreach ($categories as $category => $templates) {
        // Sort templates alphabetically within each category
        usort($templates, function($a, $b) { return strcmp($a['name'], $b['name']); });
        $categories[$category] = $templates;
    }

    file_put_contents("index.json", json_encode($categories, JSON_PRETTY_PRINT));

    update_readme($categories);
}


main();
