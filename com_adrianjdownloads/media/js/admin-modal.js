/**
 * Adrian jDownloads - inserts {adrianjd ...} tags into the article editor.
 */
(() => {
  'use strict';

  const insert = (tag) => {
    const options = (window.Joomla && Joomla.getOptions('adrianjd-modal')) || {};
    const parentJoomla = window.parent && window.parent.Joomla;

    if (!parentJoomla || !parentJoomla.editors || !parentJoomla.editors.instances[options.editor]) {
      window.prompt('Copy:', tag);
      return;
    }

    parentJoomla.editors.instances[options.editor].replaceSelection(tag);

    if (parentJoomla.Modal && parentJoomla.Modal.getCurrent()) {
      parentJoomla.Modal.getCurrent().close();
    }
  };

  document.addEventListener('click', (event) => {
    const button = event.target.closest('.adrianjd-insert');

    if (!button) {
      return;
    }

    event.preventDefault();

    const layoutInput = document.querySelector('input[name="adrianjd_layout"]:checked');
    const layout = layoutInput ? layoutInput.value : 'box';
    const id = parseInt(button.dataset.id, 10);
    const type = button.dataset.type === 'category' ? 'category' : 'file';

    insert(`{adrianjd ${type}=${id}${layout === 'link' ? ' layout=link' : ''}}`);
  });
})();
