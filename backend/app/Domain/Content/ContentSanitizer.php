<?php

declare(strict_types=1);

namespace PerrymanFinance\Domain\Content;

use PerrymanFinance\Http\Exceptions\ValidationException;

final class ContentSanitizer
{
    private const SECTION_TYPES = [
        'hero', 'rich_text', 'service_grid', 'feature_grid', 'process_steps', 'cta',
        'image_text', 'statistics', 'testimonials', 'faq_preview', 'investment_preview', 'insights_preview',
    ];

    /**
     * @param list<array<string, mixed>> $sections
     * @return list<array<string, mixed>>
     */
    public function sections(array $sections): array
    {
        $clean = [];
        foreach ($sections as $position => $section) {
            $type = $section['type'] ?? null;
            if (!is_string($type) || !in_array($type, self::SECTION_TYPES, true)) {
                throw new ValidationException(['sections' => ["Section {$position} has an unsupported type."]]);
            }
            $clean[] = ['type' => $type, 'content' => $this->value($section['content'] ?? [])];
        }
        return $clean;
    }

    public function richText(string $html): string
    {
        $html = preg_replace('#<(script|style|iframe|object|embed)[^>]*>.*?</\1>#is', '', $html) ?? '';
        $html = strip_tags($html, '<p><br><strong><em><ul><ol><li><h2><h3><h4><blockquote><a>');
        $html = preg_replace('/<([a-z0-9]+)\b[^>]*>/i', '<$1>', $html) ?? '';
        return trim($html);
    }

    public function structuredValue(mixed $value): mixed
    {
        return $this->value($value);
    }

    private function value(mixed $value): mixed
    {
        if (is_string($value)) {
            return $this->richText($value);
        }
        if (!is_array($value)) {
            return $value;
        }
        $clean = [];
        foreach ($value as $key => $item) {
            $clean[$key] = $this->value($item);
        }
        return $clean;
    }
}
