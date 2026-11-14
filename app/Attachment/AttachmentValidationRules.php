<?php

declare(strict_types=1);

namespace App\Attachment;

/**
 * What can be uploaded: up to ten files of 20 MB each, of common document,
 * image and archive types.
 */
final class AttachmentValidationRules
{
    public const string FILES = 'files';

    /**
     * @return array<string, array<int, string>>
     */
    public static function rules(): array
    {
        return [
            self::FILES => ['required', 'array', 'min:1', 'max:10'],
            self::FILES.'.*' => ['file', 'max:20480', 'mimes:jpg,jpeg,png,gif,webp,pdf,txt,csv,doc,docx,xls,xlsx,ppt,pptx,zip'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            self::FILES.'.*.max' => __('Each file can be up to 20 MB.'),
            self::FILES.'.*.mimes' => __('Upload images, PDFs, Office documents, text, CSV or zip files.'),
        ];
    }
}
