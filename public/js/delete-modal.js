
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('delete-modal');
    const cancelButton = document.getElementById('modal-cancel');
    const closeButton = document.getElementById('modal-close');
    const confirmButton = document.getElementById('modal-confirm');
    const productName = document.getElementById('delete-product-name');

    let selectedForm = null;

    if (!modal) {
        return;
    }

    function openModal(button) {
        const formId = button.dataset.formId;
        selectedForm = document.getElementById(formId);

        if (!selectedForm) {
            return;
        }

        productName.textContent = button.dataset.productName || 'this product';
        modal.hidden = false;
        confirmButton.focus();
    }

    function closeModal() {
        modal.hidden = true;
        selectedForm = null;
    }

    document.querySelectorAll('.open-delete-modal').forEach(function (button) {
        button.addEventListener('click', function () {
            openModal(button);
        });
    });

    cancelButton.addEventListener('click', closeModal);
    closeButton.addEventListener('click', closeModal);

    confirmButton.addEventListener('click', function () {
        if (selectedForm) {
            selectedForm.submit();
        }
    });

    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.hidden) {
            closeModal();
        }
    });
});
