import React from 'react';

function classNames(...values) {
    return values.filter(Boolean).join(' ');
}

export function Switch({ checked = false, onCheckedChange, className = '', disabled = false, ...props }) {
    const handleClick = () => {
        if (disabled) return;
        if (typeof onCheckedChange === 'function') {
            onCheckedChange(!checked);
        }
    };

    return (
        <button
            type="button"
            role="switch"
            aria-checked={checked}
            aria-disabled={disabled}
            onClick={handleClick}
            className={classNames(
                'relative inline-flex h-6 w-11 items-center rounded-full border transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-slate-900',
                checked
                    ? 'bg-emerald-500 border-emerald-500'
                    : 'bg-gray-200 border-gray-300 dark:bg-slate-700 dark:border-slate-600',
                disabled ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer',
                className
            )}
            {...props}
        >
            <span
                className={classNames(
                    'inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform duration-150',
                    checked ? 'translate-x-5' : 'translate-x-1'
                )}
            />
        </button>
    );
}

