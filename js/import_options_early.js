// This file is part of Moodle - https://moodle.org/.

/**
 * Insere antecipadamente a opção de cópia no formulário nativo de importação.
 *
 * O arquivo é carregado no início do body para observar a construção do formulário
 * e evitar que o campo apareça somente depois da renderização completa da página.
 *
 * @module local_courseqbankcopy/import_options_early
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
     * Retorna a chave de armazenamento do par de cursos da importação atual.
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
     * Garante que todos os módulos de banco de questões sejam importados.
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
     * Mantém o modo selecionado nos formulários nativos de importação.
     *
     * @param {HTMLFormElement[]} forms Formulários encontrados.
     * @param {string} mode Modo selecionado.
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
     * Cria o campo visual usando as classes nativas do Bootstrap/Moodle.
     *
     * @param {HTMLFormElement} form Formulário da etapa de configurações iniciais.
     * @param {string} storageKey Chave usada para manter a escolha entre etapas.
     * @param {string} initialMode Modo inicial.
     */
    const addOption = (form, storageKey, initialMode) => {
        if (document.getElementById('local-courseqbankcopy-option')) {
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
        checkbox.disabled = !config.canchoose;

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
     * Integra o modo de cópia assim que os elementos necessários aparecem no DOM.
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
        const mode = config.canchoose && stored === REUSE ? REUSE : COPY;

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
