import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';

document.querySelectorAll('[data-confirm]').forEach(form => form.addEventListener('submit', event => {
    if (!window.confirm(form.dataset.confirm)) event.preventDefault();
}));

const title = document.querySelector('#title');
const slug = document.querySelector('#slug');
if (title && slug) {
    let touched = Boolean(slug.value);
    slug.addEventListener('input', () => { touched = true; });
    title.addEventListener('input', () => {
        if (!touched) slug.value = title.value.toLowerCase().normalize('NFKD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '').slice(0, 200);
    });
}

document.querySelectorAll('[data-rich-editor]').forEach(wrapper => {
    const textarea = wrapper.querySelector('textarea');
    const surface = wrapper.querySelector('.rich-editor');
    const toolbar = wrapper.querySelector('.editor-toolbar');
    const editor = new Editor({
        element: surface,
        extensions: [StarterKit.configure({heading: {levels: [2, 3]}, link: {openOnClick: false, protocols: ['http', 'https', 'mailto']}})],
        content: textarea.value,
        editorProps: {attributes: {role: 'textbox', 'aria-label': 'Content editor', 'aria-multiline': 'true', class: 'prose'}},
        onUpdate: ({editor}) => { textarea.value = editor.getHTML(); },
    });
    textarea.hidden = true;
    textarea.required = false;
    toolbar.hidden = false;
    surface.hidden = false;
    textarea.form.addEventListener('submit', event => {
        textarea.value = editor.getHTML();
        if (editor.isEmpty) { event.preventDefault(); editor.commands.focus(); window.alert('Please enter content before saving.'); }
    });
    toolbar.addEventListener('click', event => {
        const button = event.target.closest('[data-command]');
        if (!button) return;
        const chain = editor.chain().focus();
        const actions = {bold: () => chain.toggleBold().run(), italic: () => chain.toggleItalic().run(), h2: () => chain.toggleHeading({level: 2}).run(), h3: () => chain.toggleHeading({level: 3}).run(), bullet: () => chain.toggleBulletList().run(), ordered: () => chain.toggleOrderedList().run(), quote: () => chain.toggleBlockquote().run(), undo: () => chain.undo().run(), redo: () => chain.redo().run(), link: () => {
            const href = window.prompt('Link URL (https://, http://, mailto: or /local-path). Leave empty to remove.', editor.getAttributes('link').href || 'https://');
            if (href === null) return;
            if (href === '') { chain.unsetLink().run(); return; }
            if (/^(https?:\/\/|mailto:|\/(?!\/))/i.test(href)) chain.setLink({href}).run();
            else window.alert('Please enter a valid, safe URL.');
        }};
        actions[button.dataset.command]?.();
    });
});
