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

    $verticals = new DirectoryIterator($root_dir);
    foreach ($verticals as $fileInfo) {
        if ($fileInfo->isDot() || strpos($fileInfo->getBasename(), '.') === 0 ) {continue;}
        if ($fileInfo->isDir()) {
            // Set up the category directories
            $currentCategory = explode("/", $fileInfo->getPathname())[1];
            if (!isset($categories[$currentCategory])){
                $categories[$currentCategory] = [];
            }
            
            // Set up the template directories within each category
            $templateDirectories = new DirectoryIterator($fileInfo->getPathname());
            foreach ($templateDirectories as $templateFileInfo) {
                if ($templateFileInfo->isDot() || strpos($templateFileInfo->getBasename(), '.') === 0 ) {continue;}
                if ($templateFileInfo->isDir()) { 
                    $templateName = $templateFileInfo->getFilename();
                    
                    $templateStructure = [
                        "helper_process" => "",
                        "template_process" => "",
                        "config_collection" => "",
                        "template_details" => [
                            "name" => "",
                            "card-excerpt" => "",
                            "modal-excerpt" => "",
                            "modal-description" => "",
                        ],
                        "assets" => [
                            "icon" => "",
                            "card-background" => "",
                            "slides" => [],
                        ],
                        "connected_accounts" => []
                    ];
                    
                    $categories[$currentCategory][$templateName] = $templateStructure;
                    


                    $templateContents = new DirectoryIterator($templateFileInfo->getPathname());

                    foreach ($templateContents as $fileContent) {
                        if ($fileContent->isDot() || strpos($fileContent->getBasename(), '.') === 0 ) {continue;}
                        // TODO: Handle 'Assets' directory
                        if ($fileContent->isDir()) { continue;}

                        // TODO: Handle files
                        $fileName = $fileContent->getFilename();
                        $fileName = substr($fileName, 0, strrpos($fileName, "."));
                        // var_dump($fileName);
                        
                        if ($fileName === "process_helper_export") {
                            $categories[$currentCategory][$templateName]['helper_process'] = $fileContent->getPathname();
                        } elseif ($fileName === "process_template_export") {
                            $categories[$currentCategory][$templateName]['template_process'] = $fileContent->getPathname();
                        } elseif ($fileName === "configuration_collection_export") {
                             $categories[$currentCategory][$templateName]['config_collection'] = $fileContent->getPathname();
                        }
                        
                        // elseif ($filepath === "wizard-template-details") {
                        //      $categories[$currentCategory][$templateName]->template_details->name = $data["name"];
                        // }

                        // die();
                    }
                }
                // TODO: Map template data to template structure
                
            }
        }
    }
    



    // $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root_dir));

   
    // foreach ($rii as $file) {

    //     if ($file->isDir() ) continue;
    //     if (pathinfo($file->getPathname(), PATHINFO_EXTENSION) != "json") continue;

    //     $filepath = $file->getPathname();
    //     $data = json_decode(file_get_contents($filepath), true);

    //     $categoryPath = str_replace("./", "", $file->getPath());
    //     $categorySegments = explode("/", $categoryPath);
        
    //     // Initialize the template array here
    //     $template_info = [];
    //     if (strpos($filepath, "process_helper_export") !== false) {
    //         // ... your existing code to fill $template_info ...
    //     }
        
    //     // Initialize the category structure with empty arrays and default values
    //     $currentCategory = &$categories;
    //     foreach ($categorySegments as $segment) {
    //         if (!isset($currentCategory[$segment])) {
    //             // var_dump($data["name"]);
    //             // die();
    //             $currentCategory[$segment] = [
    //                 "helper_process" => $filepath,
    //                 "template_process" => $filepath,
    //                 "config_collection" => $filepath,
    //                 "template_details" => [
    //                     "name" => "",
    //                     "card-excerpt" => "",
    //                     "modal-excerpt" => "",
    //                     "modal-description" => "",
    //                 ],
    //                 "assets" => [
    //                     "icon" => "",
    //                     "card-background" => "",
    //                     "slides" => [],
    //                 ],
    //                 "connected_accounts" => []
    //             ];
    //         }
    //         $currentCategory = &$currentCategory[$segment];
    //     }

    //     // Assign the file data to the correct key based on the filename
    //     if (strpos($filepath, "process_helper_export") !== false) {
    //         $currentCategory['helper_process'] = $template_info;
    //     } elseif (strpos($filepath, "process_template_export") !== false) {
    //         $currentCategory['template_process'] = $template_info;
    //     } elseif (strpos($filepath, "configuration_collection_export") !== false) {
    //         $currentCategory['config_collection'] = $template_info;
    //     } elseif (strpos($filepath, "wizard-template-details") !== false) {
    //         // Here, you would assign the values to 'template_details' key
    //         // For example:
    //         $currentCategory['template_details']['name'] = $data["name"];
    //         // ... and so on for other sub-keys within 'template_details'
    //     }
    //     // Repeat the process for other file types and their respective keys
    // }

    // // Once the $categories array is built, you can output it as JSON
    file_put_contents("index.json", json_encode($categories, JSON_PRETTY_PRINT));

    // // update_readme function needs to be defined if you want to use it
    // // update_readme($categories);
}

// You also need to define the compute_hash and update_readme functions if they are not already defined.


function sort_categories(&$categories) {
    ksort($categories);
    foreach ($categories as &$category) {
        if (is_array($category)) {
            sort_categories($category);
        }
    }
}


main();
