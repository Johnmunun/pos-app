import React, { useMemo } from 'react';
import ReactQuill, { Quill } from 'react-quill';
import 'react-quill/dist/quill.snow.css';

// Définir explicitement la liste de polices disponibles dans l'éditeur
const FONT_WHITELIST = [
    'times-new-roman',
    'arial',
    'calibri',
    'cambria',
    'verdana',
    'tahoma',
    'georgia',
    'courier-new',
    'garamond',
    'trebuchet-ms',
    'sans-serif',
    'serif',
];

const Font = Quill.import('formats/font');
Font.whitelist = FONT_WHITELIST;
Quill.register(Font, true);

export default function RichTextEditor({ value, onChange, placeholder }) {
    const modules = useMemo(
        () => ({
            toolbar: [
                [{ font: FONT_WHITELIST }, { size: ['small', false, 'large', 'huge'] }],
                [{ header: [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                [{ align: [] }],
                ['link'],
                ['clean'],
            ],
        }),
        []
    );

    return (
        <div className="border border-gray-300 dark:border-slate-600 rounded-md overflow-hidden bg-white dark:bg-slate-800">
            <ReactQuill
                theme="snow"
                value={value || ''}
                onChange={onChange}
                modules={modules}
                placeholder={placeholder}
            />
        </div>
    );
}

