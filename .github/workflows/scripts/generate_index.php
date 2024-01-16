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

function main()
{
    $rootDirectory = ".";
    $categories = [];

    // Iterate over category directories
    foreach (new DirectoryIterator($rootDirectory) as $categoryInfo) {
        if ($categoryInfo->isDot() || strpos($categoryInfo->getBasename(), ".") === 0) {
            continue;
        }

        if ($categoryInfo->isDir()) {
            $currentCategory = $categoryInfo->getFilename();
            
            if (!isset($categories[$currentCategory])) {
                $categories[$currentCategory] = [];
            }

            // Iterate over template directories within each category
            foreach (new DirectoryIterator($categoryInfo->getPathname()) as $templateInfo) {
                if ($templateInfo->isDot() || strpos($templateInfo->getBasename(), ".") === 0) {
                    continue;
                }

                if ($templateInfo->isDir()) {
                    $templateName = $templateInfo->getFilename();
                    $categories[$currentCategory][$templateName] = initializeTemplateStructure();

                    // Iterate over template contents
                    foreach (new DirectoryIterator($templateInfo->getPathname()) as $contentInfo) {
                        if ($contentInfo->isDot() || strpos($templateInfo->getBasename(), ".") === 0) {
                            continue;
                        }

                        handleTemplateContent($contentInfo, $categories, $currentCategory, $templateName);
                    }
                }
            }
        }
    }

    file_put_contents("index.json", json_encode($categories, JSON_PRETTY_PRINT));
}

function initializeTemplateStructure()
{
    return [
        "helper_process" => "",
        "template_process" => "",
        "config_collection" => "",
        "template_details" => [
            "card-title" => "",
            "card-excerpt" => "",
            "modal-excerpt" => "",
            "modal-description" => "",
            'version' => "",
            'unique-template-id' => "",
        ],
        "assets" => [
            "icon" => "",
            "card-background" => "",
            "slides" => [],
        ],
        "connected_accounts" => []
    ];
}

function handleTemplateContent($contentInfo, &$categories, $currentCategory, $templateName)
{
    if ($contentInfo->isDir()) {
        handleAssetDirectory($contentInfo, $categories, $currentCategory, $templateName);
    } else {
        mapContentToTemplateStructure($contentInfo, $categories, $currentCategory, $templateName);
    }
}

function handleAssetDirectory($assetDirectory, &$categories, $currentCategory, $templateName)
{
    $assets = new DirectoryIterator($assetDirectory->getPathname());
    
    foreach ($assets as $assetFileInfo) {
        if ($assetFileInfo->isDot() || strpos($assetFileInfo->getBasename(), '.') === 0) {
            continue;
        }

        handleAssetFile($assetFileInfo, $categories, $currentCategory, $templateName);
    }
}

function handleAssetFile($assetFileInfo, &$categories, $currentCategory, $templateName)
{
    $assetName = $assetFileInfo->getFilename();
    $assetName = substr($assetName, 0, strrpos($assetName, "."));

    if ($assetName === 'card-background') {
        $categories[$currentCategory][$templateName]['assets']['card-background'] = $assetFileInfo->getPathname();
    }

    if ($assetName === 'icon') {
        $categories[$currentCategory][$templateName]['assets']['icon'] = $assetFileInfo->getPathname();
    }

    if ($assetFileInfo->isDir()) {
        handleSlidesDirectory($assetFileInfo, $categories, $currentCategory, $templateName);
    }
}

function handleSlidesDirectory($slidesDirectory, &$categories, $currentCategory, $templateName)
{
    $slides = new DirectoryIterator($slidesDirectory->getPathname());

    foreach ($slides as $slideInfo) {
        if ($slides->isDot() || strpos($slides->getBasename(), '.') === 0) {
            continue;
        }

        array_push($categories[$currentCategory][$templateName]['assets']['slides'], $slideInfo->getPathname());
    }
}

function mapContentToTemplateStructure($contentInfo, &$categories, $currentCategory, $templateName)
{
    $fileName = $contentInfo->getFilename();
    $fileName = substr($fileName, 0, strrpos($fileName, "."));

    switch ($fileName) {
        case "process_helper_export":
            $categories[$currentCategory][$templateName]['helper_process'] = $contentInfo->getPathname();
            break;
        case "process_template_export":
            $categories[$currentCategory][$templateName]['template_process'] = $contentInfo->getPathname();
            break;
        case "wizard-template-details":
            loadXmlAttributes($contentInfo, $categories, $currentCategory, $templateName);
            break;
    }
}

function loadXmlAttributes($contentInfo, &$categories, $currentCategory, $templateName)
{
    $xml = simplexml_load_file($contentInfo->getPathname());

    $cardTitle = (string) $xml->attributes()['card-title'];
    $cardExcerpt = (string) $xml->attributes()['card-excerpt'];
    $modelExcerpt = (string) $xml->attributes()['modal-excerpt'];
    $modelDescription = (string) $xml->attributes()['modal-description'];
    $version = (string) $xml->attributes()['version'];
    $uniqueTemplateId = (string) $xml->attributes()['unique-template-id'];

    $categories[$currentCategory][$templateName]['template_details']['card-title'] = $cardTitle;
    $categories[$currentCategory][$templateName]['template_details']['card-excerpt'] = $cardExcerpt;
    $categories[$currentCategory][$templateName]['template_details']['modal-excerpt'] = $modelExcerpt;
    $categories[$currentCategory][$templateName]['template_details']['modal-description'] = $modelDescription;
    $categories[$currentCategory][$templateName]['template_details']['version'] = $version;
    $categories[$currentCategory][$templateName]['template_details']['unique-template-id'] = $uniqueTemplateId;
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
