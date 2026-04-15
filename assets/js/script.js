document.addEventListener('DOMContentLoaded', () => {
    const getDeleteDialog = () => {
        let dialog = document.querySelector('[data-delete-dialog]');
        if (dialog) {
            return dialog;
        }

        dialog = document.createElement('div');
        dialog.className = 'delete-dialog-backdrop';
        dialog.setAttribute('data-delete-dialog', '');
        dialog.innerHTML = `
            <div class="delete-dialog-card" role="dialog" aria-modal="true" aria-labelledby="deleteDialogTitle">
                <div class="delete-dialog-kicker">Delete Confirmation</div>
                <h3 id="deleteDialogTitle">Remove this item?</h3>
                <p class="delete-dialog-message">This action cannot be undone.</p>
                <div class="delete-dialog-actions">
                    <button type="button" class="delete-dialog-btn delete-dialog-btn-secondary" data-delete-cancel>Cancel</button>
                    <button type="button" class="delete-dialog-btn delete-dialog-btn-danger" data-delete-confirm>Delete</button>
                </div>
            </div>
        `;
        document.body.appendChild(dialog);
        return dialog;
    };

    const showDeleteDialog = (message) => new Promise((resolve) => {
        const dialog = getDeleteDialog();
        const messageNode = dialog.querySelector('.delete-dialog-message');
        const cancelButton = dialog.querySelector('[data-delete-cancel]');
        const confirmButton = dialog.querySelector('[data-delete-confirm]');

        const cleanup = (result) => {
            dialog.classList.remove('is-visible');
            cancelButton.removeEventListener('click', handleCancel);
            confirmButton.removeEventListener('click', handleConfirm);
            dialog.removeEventListener('click', handleBackdrop);
            document.removeEventListener('keydown', handleEscape);
            resolve(result);
        };

        const handleCancel = () => cleanup(false);
        const handleConfirm = () => cleanup(true);
        const handleBackdrop = (event) => {
            if (event.target === dialog) {
                cleanup(false);
            }
        };
        const handleEscape = (event) => {
            if (event.key === 'Escape') {
                cleanup(false);
            }
        };

        messageNode.textContent = message;
        dialog.classList.add('is-visible');
        cancelButton.addEventListener('click', handleCancel);
        confirmButton.addEventListener('click', handleConfirm);
        dialog.addEventListener('click', handleBackdrop);
        document.addEventListener('keydown', handleEscape);
        confirmButton.focus();
    });

    const imageInput = document.querySelector('#image');
    const imagePreview = document.querySelector('#imagePreview');
    const modalImageInput = document.querySelector('#modal_image');
    const modalImagePreview = document.querySelector('#modalImagePreview');

    if (imageInput && imagePreview) {
        imageInput.addEventListener('change', (event) => {
            const file = event.target.files?.[0];
            if (!file) {
                return;
            }

            const reader = new FileReader();
            reader.onload = (loadEvent) => {
                imagePreview.src = loadEvent.target?.result || imagePreview.src;
            };
            reader.readAsDataURL(file);
        });
    }

    if (modalImageInput && modalImagePreview) {
        modalImageInput.addEventListener('change', (event) => {
            const file = event.target.files?.[0];
            if (!file) {
                return;
            }

            const reader = new FileReader();
            reader.onload = (loadEvent) => {
                modalImagePreview.src = loadEvent.target?.result || modalImagePreview.src;
            };
            reader.readAsDataURL(file);
        });
    }

    const addClothingModal = document.querySelector('#addClothingModal');
    if (addClothingModal && addClothingModal.dataset.openOnLoad === '1' && window.bootstrap) {
        const modal = new window.bootstrap.Modal(addClothingModal);
        modal.show();
    }

    document.querySelectorAll('[data-confirm-delete]').forEach((link) => {
        link.addEventListener('click', async (event) => {
            const message = link.dataset.confirmMessage || 'Delete this item? This action cannot be undone.';
            event.preventDefault();
            const confirmed = await showDeleteDialog(message);
            if (!confirmed) {
                return;
            }

            window.location.href = link.href;
        });
    });

    const fitBuilderForm = document.querySelector('#fitBuilderForm');
    const fitCount = document.querySelector('[data-fit-count]');
    const fitPreview = document.querySelector('[data-fit-preview]');
    const fitItems = document.querySelectorAll('[data-fit-item]');

    if (fitBuilderForm && fitCount && fitPreview && fitItems.length) {
        const updateFitBuilder = () => {
            const selected = [...fitItems].filter((input) => input.checked);
            fitCount.textContent = `${selected.length} selected`;

            if (!selected.length) {
                fitPreview.innerHTML = '<span class="text-muted small">No items selected yet.</span>';
                return;
            }

            fitPreview.innerHTML = selected
                .map((input) => `<span>${input.dataset.itemName}</span>`)
                .join('');
        };

        fitItems.forEach((input) => {
            input.addEventListener('change', updateFitBuilder);
        });

        fitBuilderForm.addEventListener('submit', (event) => {
            const selected = [...fitItems].filter((input) => input.checked);
            if (selected.length < 2) {
                event.preventDefault();
                window.alert('Select at least 2 clothing items to save a fit.');
            }
        });

        updateFitBuilder();
    }

    const recommendationForm = document.querySelector('#recommendationForm');
    const recommendationResult = document.querySelector('#recommendationResult');

    if (recommendationForm && recommendationResult) {
        recommendationForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            recommendationResult.innerHTML = '<p class="text-muted mb-0">Generating outfit options...</p>';

            const formData = new FormData(recommendationForm);

            try {
                const response = await fetch('../actions/get_recommendation.php', {
                    method: 'POST',
                    body: formData,
                });

                const data = await response.json();
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Unable to generate recommendation.');
                }

                const result = data.result;
                const engineLabel = result.engine === 'openai'
                    ? 'Generated with OpenAI styling.'
                    : result.engine === 'ml_fallback'
                        ? 'Generated with the local ML recommender fallback.'
                        : '';
                const outfits = (result.outfits || []).map((outfit, index) => {
                    const wearGuide = (outfit.wear_guide || []).map((line) => `<li>${line}</li>`).join('');
                    const items = (outfit.items || []).map((item) => `
                        <div class="recommendation-item p-3 rounded-4 border bg-white bg-opacity-50">
                            <h5 class="mb-1">${item.name}</h5>
                            <p class="mb-2 text-muted">${item.category} / ${item.color || 'No color'} / ${item.occasion}</p>
                            <p class="mb-0 small text-muted">Wear count: ${item.wear_count}</p>
                        </div>
                    `).join('');
                    const wearGuideSection = wearGuide
                        ? `<div class="recommendation-item p-3 rounded-4 border bg-white bg-opacity-50 mb-3">
                            <h5 class="mb-2">What To Wear</h5>
                            <ul class="mb-0">${wearGuide}</ul>
                        </div>`
                        : '';
                    const itemSection = items
                        ? `<div class="mb-2"><h6 class="mb-3">Recommended Clothes</h6><div class="row g-3 mb-3">${items}</div></div>`
                        : `<div class="recommendation-item p-3 rounded-4 border bg-white bg-opacity-50 mb-3">
                            <h5 class="mb-1">Fit Direction</h5>
                            <p class="mb-0 text-muted">This recommendation is text-based because the wardrobe does not yet have enough matching pieces for a full AI-built outfit set.</p>
                        </div>`;

                    const reasons = (outfit.reasons || []).map((reason) => `<li>${reason}</li>`).join('');

                    return `
                        <section class="feature-card recommendation-option mb-3">
                            <span class="mini-label">Outfit ${index + 1}</span>
                            <h4 class="mt-2 mb-2">${outfit.title || `Look ${index + 1}`}</h4>
                            <p class="text-muted mb-3">${outfit.summary || ''}</p>
                            ${wearGuideSection}
                            ${itemSection}
                            <h6>Why this works</h6>
                            <ul class="mb-0">${reasons}</ul>
                        </section>
                    `;
                }).join('');

                recommendationResult.innerHTML = `
                    <h3>${result.message}</h3>
                    <p class="text-muted mb-4">${result.access || ''}</p>
                    ${engineLabel ? `<p class="text-muted small mb-4">${engineLabel}</p>` : ''}
                    ${outfits || '<p class="text-muted">No outfits matched your current wardrobe.</p>'}
                `;
            } catch (error) {
                recommendationResult.innerHTML = `<div class="alert alert-danger mb-0">${error.message}</div>`;
            }
        });
    }
});
