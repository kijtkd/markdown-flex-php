<?php

declare(strict_types=1);

namespace MdFlex;

final class FlexValidator
{
    private array $errors = [];
    private SizeCalculator $sizeCalculator;

    public function __construct()
    {
        $this->sizeCalculator = new SizeCalculator();
    }

    public function validate(array $data): array
    {
        $this->errors = [];

        if (isset($data['type'])) {
            switch ($data['type']) {
                case 'flex':
                    $this->validateFlexMessage($data);
                    break;
                case 'bubble':
                    $this->validateBubble($data);
                    break;
                case 'carousel':
                    $this->validateCarousel($data);
                    break;
                default:
                    $this->addError('Root type must be "flex", "bubble", or "carousel"');
            }
        } else {
            $this->addError('Root message must have a type');
        }

        return $this->errors;
    }

    private function validateFlexMessage(array $message): void
    {
        if (!isset($message['altText'])) {
            $this->addError('altText is required');
        } else {
            $this->validateAltText($message['altText']);
        }

        if (!isset($message['contents'])) {
            $this->addError('contents is required');
        } else {
            $this->validateContents($message['contents']);
        }
    }

    private function validateAltText(string $altText): void
    {
        if (mb_strlen($altText, 'UTF-8') > 400) {
            $this->addError('altText must be 400 characters or less');
        }

        if (empty(trim($altText))) {
            $this->addError('altText cannot be empty');
        }
    }

    private function validateContents(array $contents): void
    {
        if (!isset($contents['type'])) {
            $this->addError('contents.type is required');
            return;
        }

        switch ($contents['type']) {
            case 'bubble':
                $this->validateBubble($contents);
                break;
            case 'carousel':
                $this->validateCarousel($contents);
                break;
            default:
                $this->addError('contents.type must be "bubble" or "carousel"');
        }
    }

    private function validateBubble(array $bubble): void
    {
        // Size check
        $size = $this->sizeCalculator->jsonSize($bubble);
        if ($size > 10240) {
            $this->addError("Bubble size ({$size} bytes) exceeds 10KB limit");
        }

        // Size validation
        if (isset($bubble['size']) && !in_array($bubble['size'], ['nano', 'micro', 'kilo', 'mega', 'giga'])) {
            $this->addError('bubble.size must be one of: nano, micro, kilo, mega, giga');
        }

        // Body or Hero is required
        if (!isset($bubble['body']) && !isset($bubble['hero'])) {
            $this->addError('bubble.body or bubble.hero is required');
        }
        
        if (isset($bubble['body'])) {
            $this->validateContainer($bubble['body'], 'bubble.body');
        }

        // Optional sections
        if (isset($bubble['hero'])) {
            $this->validateContainer($bubble['hero'], 'bubble.hero');
        }

        if (isset($bubble['header'])) {
            $this->validateContainer($bubble['header'], 'bubble.header');
        }

        if (isset($bubble['footer'])) {
            // Footer can be a container or have separator: true
            if (isset($bubble['footer']['separator']) && $bubble['footer']['separator'] === true) {
                // Valid separator footer
            } else {
                $this->validateContainer($bubble['footer'], 'bubble.footer');
            }
        }
    }

    private function validateCarousel(array $carousel): void
    {
        if (!isset($carousel['contents'])) {
            $this->addError('carousel.contents is required');
            return;
        }

        if (!is_array($carousel['contents'])) {
            $this->addError('carousel.contents must be an array');
            return;
        }

        $bubbleCount = count($carousel['contents']);
        if ($bubbleCount > 10) {
            $this->addError("Carousel contains {$bubbleCount} bubbles, maximum is 10");
        }

        if ($bubbleCount === 0) {
            $this->addError('Carousel must contain at least 1 bubble');
        }

        // Total size check
        $totalSize = $this->sizeCalculator->jsonSize($carousel);
        if ($totalSize > 51200) {
            $this->addError("Carousel size ({$totalSize} bytes) exceeds 50KB limit");
        }

        foreach ($carousel['contents'] as $index => $bubble) {
            if (!is_array($bubble)) {
                $this->addError("carousel.contents[{$index}] must be an object");
                continue;
            }

            if (!isset($bubble['type']) || $bubble['type'] !== 'bubble') {
                $this->addError("carousel.contents[{$index}].type must be \"bubble\"");
                continue;
            }

            $this->validateBubble($bubble);
        }
    }

    private function validateContainer(array $container, string $path): void
    {
        if (!isset($container['type'])) {
            $this->addError("{$path}.type is required");
            return;
        }

        switch ($container['type']) {
            case 'box':
                $this->validateBox($container, $path);
                break;
            case 'text':
                $this->validateText($container, $path);
                break;
            case 'image':
                $this->validateImage($container, $path);
                break;
            case 'button':
                $this->validateButton($container, $path);
                break;
            case 'icon':
                $this->validateIcon($container, $path);
                break;
            case 'separator':
            case 'spacer':
            case 'filler':
                // These components have minimal requirements
                break;
            default:
                $this->addError("{$path}.type \"{$container['type']}\" is not supported");
        }
    }

    private function validateBox(array $box, string $path): void
    {
        if (!isset($box['layout'])) {
            $this->addError("{$path}.layout is required");
        } elseif (!in_array($box['layout'], ['vertical', 'horizontal', 'baseline'])) {
            $this->addError("{$path}.layout must be one of: vertical, horizontal, baseline");
        }

        if (!isset($box['contents'])) {
            $this->addError("{$path}.contents is required");
        } elseif (!is_array($box['contents'])) {
            $this->addError("{$path}.contents must be an array");
        } else {
            foreach ($box['contents'] as $index => $content) {
                if (is_array($content)) {
                    $this->validateContainer($content, "{$path}.contents[{$index}]");
                }
            }
        }

        // Validate optional properties
        $this->validateSpacing($box, $path);
        $this->validateMargin($box, $path);
    }

    private function validateText(array $text, string $path): void
    {
        // Either text or contents is required
        if (!isset($text['text']) && !isset($text['contents'])) {
            $this->addError("{$path}.text or {$path}.contents is required");
        }
        
        if (isset($text['text'])) {
            $textLength = mb_strlen($text['text'], 'UTF-8');
            if ($textLength > 2000) {
                $this->addError("{$path}.text length ({$textLength}) exceeds 2000 characters");
            }
        }
        
        if (isset($text['contents'])) {
            $this->validateTextContents($text['contents'], $path);
        }

        // Validate optional properties
        if (isset($text['size']) && !in_array($text['size'], ['xxs', 'xs', 'sm', 'md', 'lg', 'xl', 'xxl', '3xl', '4xl', '5xl'])) {
            $this->addError("{$path}.size must be valid size value");
        }

        if (isset($text['weight']) && !in_array($text['weight'], ['regular', 'bold'])) {
            $this->addError("{$path}.weight must be \"regular\" or \"bold\"");
        }

        if (isset($text['color']) && !$this->isValidColor($text['color'])) {
            $this->addError("{$path}.color must be valid color code");
        }
    }

    private function validateTextContents(array $contents, string $path): void
    {
        if (!is_array($contents)) {
            $this->addError("{$path}.contents must be an array");
            return;
        }

        foreach ($contents as $index => $content) {
            if (is_string($content)) {
                // Plain text content
                if (mb_strlen($content, 'UTF-8') > 2000) {
                    $this->addError("{$path}.contents[{$index}] text length exceeds 2000 characters");
                }
            } elseif (is_array($content)) {
                // Span content
                if (!isset($content['type']) || $content['type'] !== 'span') {
                    $this->addError("{$path}.contents[{$index}].type must be 'span'");
                }
                
                if (!isset($content['text'])) {
                    $this->addError("{$path}.contents[{$index}].text is required");
                } else {
                    $textLength = mb_strlen($content['text'], 'UTF-8');
                    if ($textLength > 2000) {
                        $this->addError("{$path}.contents[{$index}].text length ({$textLength}) exceeds 2000 characters");
                    }
                }
            }
        }
    }

    private function validateImage(array $image, string $path): void
    {
        if (!isset($image['url'])) {
            $this->addError("{$path}.url is required");
        } elseif (!filter_var($image['url'], FILTER_VALIDATE_URL)) {
            $this->addError("{$path}.url must be valid URL");
        }

        if (isset($image['aspectRatio']) && !preg_match('/^\d+:\d+$/', $image['aspectRatio'])) {
            $this->addError("{$path}.aspectRatio must be in format \"width:height\"");
        }
    }

    private function validateIcon(array $icon, string $path): void
    {
        if (!isset($icon['url'])) {
            $this->addError("{$path}.url is required");
        } elseif (!filter_var($icon['url'], FILTER_VALIDATE_URL)) {
            $this->addError("{$path}.url must be valid URL");
        }

        if (isset($icon['size']) && !in_array($icon['size'], ['xxs', 'xs', 'sm', 'md', 'lg', 'xl', 'xxl', '3xl', '4xl', '5xl'])) {
            $this->addError("{$path}.size must be valid size value");
        }
    }

    private function validateButton(array $button, string $path): void
    {
        if (!isset($button['action'])) {
            $this->addError("{$path}.action is required");
        } else {
            $this->validateAction($button['action'], "{$path}.action");
        }

        if (isset($button['style']) && !in_array($button['style'], ['primary', 'secondary', 'link'])) {
            $this->addError("{$path}.style must be one of: primary, secondary, link");
        }
    }

    private function validateAction(array $action, string $path): void
    {
        if (!isset($action['type'])) {
            $this->addError("{$path}.type is required");
            return;
        }

        switch ($action['type']) {
            case 'uri':
                if (!isset($action['uri'])) {
                    $this->addError("{$path}.uri is required");
                } elseif (!filter_var($action['uri'], FILTER_VALIDATE_URL)) {
                    $this->addError("{$path}.uri must be valid URL");
                }
                break;
            case 'message':
                if (!isset($action['text'])) {
                    $this->addError("{$path}.text is required");
                }
                break;
            case 'postback':
                if (!isset($action['data'])) {
                    $this->addError("{$path}.data is required");
                }
                break;
            default:
                $this->addError("{$path}.type \"{$action['type']}\" is not supported");
        }
    }

    private function validateSpacing(array $component, string $path): void
    {
        if (isset($component['spacing']) && !in_array($component['spacing'], ['none', 'xs', 'sm', 'md', 'lg', 'xl', 'xxl'])) {
            $this->addError("{$path}.spacing must be valid spacing value");
        }
    }

    private function validateMargin(array $component, string $path): void
    {
        if (isset($component['margin']) && !in_array($component['margin'], ['none', 'xs', 'sm', 'md', 'lg', 'xl', 'xxl'])) {
            $this->addError("{$path}.margin must be valid margin value");
        }
    }

    private function isValidColor(string $color): bool
    {
        // Hex color validation
        if (preg_match('/^#[0-9a-fA-F]{6}([0-9a-fA-F]{2})?$/', $color)) {
            return true;
        }

        // Named colors (basic set)
        $namedColors = ['white', 'black', 'red', 'green', 'blue', 'yellow', 'orange', 'purple', 'pink', 'gray'];
        return in_array(strtolower($color), $namedColors);
    }

    private function addError(string $message): void
    {
        $this->errors[] = $message;
    }

    public function isValid(array $data): bool
    {
        return empty($this->validate($data));
    }

    public function validateBubbleOrCarousel(array $data): array
    {
        // For standalone bubble/carousel validation
        return $this->validate($data);
    }
}