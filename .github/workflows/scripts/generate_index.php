<?php

function compute_hash($data) {
    return $data ? sha1($data) : null;
}

function update_readme($categories) {
    $readme = fopen("README.md", "w");
    fwrite($readme,
        "# ProcessMaker Template Wizards\nExplore our ready-to-go Process Templates to kick-start your automation across several use cases and industries.  Deploy into your Platform instance to spin up new processes anduse as-is with all necessary assets included. Or customize and extend as needed to carve out an ideal solution fit for you."
    );
    ksort($categories);  // Sort categories alphabetically
    foreach ($categories as $category => $templates) {
        $category = str_replace("-", " ", $category);
        $category = ucwords($category);
        fwrite($readme, "\n## $category\n");
        // Sort templates alphabetically within each category
        usort($templates, function($a, $b) { return strcmp($a['name'], $b['name']); });
        foreach ($templates as $template) {
            $string = "- **[{$template['name']}](/{$template['relative_path']})**: {$template['description']}";
            if ($template['version']) {
                $string .= " (Version {$template['version']})\n";
            } else {
                $string .= "\n";
            }
            fwrite($readme, $string);
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
        $category = str_replace("./", "", $file->getPath());
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

        if (!isset($categories[$category])) {
            $categories[$category] = [];
        }

        $categories[$category][] = $template_info;
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
