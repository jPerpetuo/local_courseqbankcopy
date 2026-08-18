// This file is part of Moodle - https://moodle.org/.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Adds the copy option early to the native course import form.
 *
 * This file is loaded early in the body to observe the native form construction
 * and prevent the field from appearing only after the full page has rendered.
 *
 * @module     local_courseqbankcopy/import_options_early
 * @copyright  2026 jPerpetuo <joao.ariel@crearenet.com.br>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

(function() {
    'use strict';

    const COPY = 'copy';
    const REUSE = 'reuse';
    const configElement = document.getElementById('local-courseqbankcopy-config');

    if (!configElement) {
        return;
    }

    let config;
    try {
        config = JSON.parse(configElement.textContent);
    } catch (error) {
        return;
    }

    /**
     * Returns the storage key for the current source and target course pair.
     *
     * @returns {string}
     */
    const getStorageKey = () => {
        const params = new URLSearchParams(window.location.search);
        const target = params.get('id') || '0';
        const source = params.get('importid')
            || document.querySelector('input[name="importid"]')?.value
            || '0';
        return `local_courseqbankcopy:${target}:${source}`;
    };

    /**
     * Ensures that all question bank modules are included in the import.
     */
    const includeQuestionBanks = () => {
        document.querySelectorAll('input[type="checkbox"][name^="qbank_"][name$="_included"]')
            .forEach((input) => {
                if (!input.checked && !input.disabled) {
                    input.click();
                }
            });
    };

    /**
     * Keeps the selected mode in the native import forms.
     *
     * @param {HTMLFormElement[]} forms Import forms found on the page.
     * @param {string} mode Selected import mode.
     */
    const setFormMode = (forms, mode) => {
        forms.forEach((form) => {
            let input = form.querySelector(`input[name="${config.modeparameter}"]`);
            if (!input) {
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = config.modeparameter;
                form.append(input);
            }
            input.value = mode;
        });
    };

    /**
     * Creates the visual field using native Bootstrap and Moodle classes.
     *
     * @param {HTMLFormElement} form Form for the initial settings step.
     * @param {string} storageKey Key used to retain the selection between steps.
     * @param {string} initialMode Initial import mode.
     */
    const addOption = (form, storageKey, initialMode) => {
        if (!config.canchoose || document.getElementById('local-courseqbankcopy-option')) {
            return;
        }

        let mode = initialMode;
        const container = document.createElement('div');
        container.id = 'local-courseqbankcopy-option';
        container.className = 'root_setting';

        const checkWrapper = document.createElement('div');
        checkWrapper.className = 'form-check';

        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.id = 'local-courseqbankcopy-checkbox';
        checkbox.className = 'form-check-input';
        checkbox.checked = mode === COPY;

        const label = document.createElement('label');
        label.className = 'form-check-label';
        label.htmlFor = checkbox.id;
        label.textContent = config.copylabel;

        checkWrapper.append(checkbox, label);
        container.append(checkWrapper);

        const firstRootSetting = form.querySelector('.root_setting');
        if (firstRootSetting) {
            firstRootSetting.before(container);
        } else {
            const firstSetting = form.querySelector('input[name="setting_root_activities"]');
            firstSetting.parentElement.before(container);
        }

        checkbox.addEventListener('change', () => {
            mode = checkbox.checked ? COPY : REUSE;
            window.sessionStorage.setItem(storageKey, mode);
            setFormMode([form], mode);
            if (mode === COPY) {
                includeQuestionBanks();
            }
        });

        form.addEventListener('submit', () => {
            setFormMode([form], mode);
            if (mode === COPY) {
                includeQuestionBanks();
            }
        });
    };

    /**
     * Integrates copy mode as soon as the required elements appear in the DOM.
     */
    const render = () => {
        const importForms = Array.from(document.querySelectorAll('form'))
            .filter((form) => form.action.includes('/backup/import.php'));
        if (!importForms.length) {
            return;
        }

        const params = new URLSearchParams(window.location.search);
        const sourceInput = document.querySelector('input[name="importid"]');
        const hasSelectedSource = params.has('importid')
            || (sourceInput && sourceInput.type === 'hidden');
        const isCourseSelection = importForms.some((form) =>
            form.querySelector('input[type="radio"][name="importid"]'));
        if (!hasSelectedSource || isCourseSelection) {
            return;
        }

        const storageKey = getStorageKey();
        const stored = window.sessionStorage.getItem(storageKey);
        const storedModeIsValid = stored === COPY || stored === REUSE;
        const defaultMode = config.defaultmode === REUSE ? REUSE : COPY;
        const mode = config.canchoose && storedModeIsValid ? stored : defaultMode;

        setFormMode(importForms, mode);
        if (mode === COPY) {
            includeQuestionBanks();
        }

        const initialForm = importForms.find((form) =>
            form.querySelector('input[type="checkbox"][name="setting_root_activities"]'));
        if (initialForm) {
            addOption(initialForm, storageKey, mode);
        }
    };

    let renderScheduled = false;
    const scheduleRender = () => {
        if (renderScheduled) {
            return;
        }
        renderScheduled = true;
        window.queueMicrotask(() => {
            renderScheduled = false;
            render();
        });
    };

    const observer = new MutationObserver(scheduleRender);
    observer.observe(document.documentElement, {
        childList: true,
        subtree: true,
    });

    document.addEventListener('DOMContentLoaded', () => {
        observer.disconnect();
        render();
    }, {once: true});

    scheduleRender();
})();
