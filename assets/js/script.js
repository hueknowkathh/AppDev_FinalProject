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

    const recommendationForm = document.querySelector('#aiStylistForm');
    const recommendationChatLog = document.querySelector('#aiStylistChatLog');
    const recommendationPrompt = document.querySelector('#aiPrompt');
    const chatFillButtons = document.querySelectorAll('[data-chat-fill]');
    const aiStylistBackdrop = document.querySelector('[data-ai-stylist-backdrop]');
    const aiStylistPanel = document.querySelector('#aiStylistPanel');
    const aiStylistOpenButtons = document.querySelectorAll('[data-ai-stylist-open]');
    const aiStylistCloseButton = document.querySelector('[data-ai-stylist-close]');
    const aiStylistResetButton = document.querySelector('[data-ai-stylist-reset]');
    const aiStylistComposer = document.querySelector('[data-ai-stylist-composer]');
    const aiStylistLauncherWrap = document.querySelector('.ai-stylist-launcher-wrap');
    const aiStylistLauncher = document.querySelector('[data-ai-stylist-launcher]');

    const launcherStorageKey = 'closetcouture.aiStylistLauncherPos';

    const applyLauncherPosition = (position) => {
        if (!aiStylistLauncherWrap || !position) {
            return;
        }

        aiStylistLauncherWrap.style.left = `${position.left}px`;
        aiStylistLauncherWrap.style.top = `${position.top}px`;
        aiStylistLauncherWrap.style.right = 'auto';
        aiStylistLauncherWrap.style.bottom = 'auto';
    };

    const isCompactViewport = () => window.matchMedia('(max-width: 767.98px)').matches;

    const clampLauncherPosition = (left, top) => {
        if (!aiStylistLauncherWrap) {
            return { left, top };
        }

        const rect = aiStylistLauncherWrap.getBoundingClientRect();
        const maxLeft = window.innerWidth - rect.width - 12;
        const maxTop = window.innerHeight - rect.height - 12;

        return {
            left: Math.max(12, Math.min(left, maxLeft)),
            top: Math.max(12, Math.min(top, maxTop)),
        };
    };

    const setAiStylistState = (isOpen) => {
        if (!aiStylistBackdrop || !aiStylistPanel) {
            return;
        }

        aiStylistBackdrop.hidden = !isOpen;
        aiStylistBackdrop.style.display = isOpen ? 'flex' : 'none';
        aiStylistBackdrop.classList.toggle('is-visible', isOpen);
        document.body.classList.toggle('ai-stylist-open', isOpen);
        aiStylistOpenButtons.forEach((button) => {
            button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        if (isOpen) {
            window.setTimeout(() => recommendationPrompt?.focus(), 40);
        }
    };

    const setAiStylistComposerState = (collapsed) => {
        if (!aiStylistComposer || !aiStylistResetButton) {
            return;
        }

        aiStylistComposer.hidden = collapsed;
        aiStylistPanel?.classList.toggle('ai-stylist-results-mode', collapsed);
        aiStylistResetButton.hidden = !collapsed;
    };

    if (aiStylistOpenButtons.length && aiStylistBackdrop && aiStylistPanel) {
        aiStylistOpenButtons.forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                setAiStylistState(true);
            });
        });

        const forceCloseAiStylist = (event) => {
            event?.preventDefault();
            event?.stopPropagation();
            setAiStylistState(false);
        };

        aiStylistCloseButton?.addEventListener('click', forceCloseAiStylist);
        aiStylistCloseButton?.addEventListener('pointerup', forceCloseAiStylist);

        aiStylistPanel.addEventListener('click', (event) => {
            const closeTrigger = event.target.closest('[data-ai-stylist-close]');
            if (closeTrigger) {
                forceCloseAiStylist(event);
            }
        });
        aiStylistBackdrop.addEventListener('click', (event) => {
            if (event.target === aiStylistBackdrop) {
                setAiStylistState(false);
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && aiStylistBackdrop.classList.contains('is-visible')) {
                setAiStylistState(false);
            }
        });

        const params = new URLSearchParams(window.location.search);
        if (params.get('open_ai_stylist') === '1') {
            setAiStylistState(true);
        }
    }

    aiStylistResetButton?.addEventListener('click', () => {
        setAiStylistComposerState(false);
        recommendationPrompt?.focus();
    });

    if (aiStylistLauncherWrap && aiStylistLauncher) {
        try {
            const savedPosition = JSON.parse(window.localStorage.getItem(launcherStorageKey) || 'null');
            if (!isCompactViewport() && savedPosition && Number.isFinite(savedPosition.left) && Number.isFinite(savedPosition.top)) {
                applyLauncherPosition(clampLauncherPosition(savedPosition.left, savedPosition.top));
            }
        } catch (error) {
            window.localStorage.removeItem(launcherStorageKey);
        }

        let dragState = null;

        aiStylistLauncher.addEventListener('pointerdown', (event) => {
            if (event.button !== 0) {
                return;
            }

            if (isCompactViewport()) {
                dragState = null;
                return;
            }

            const rect = aiStylistLauncherWrap.getBoundingClientRect();
            dragState = {
                pointerId: event.pointerId,
                offsetX: event.clientX - rect.left,
                offsetY: event.clientY - rect.top,
                startX: event.clientX,
                startY: event.clientY,
                moved: false,
            };

            aiStylistLauncher.setPointerCapture(event.pointerId);
            aiStylistLauncherWrap.classList.add('is-dragging');
        });

        aiStylistLauncher.addEventListener('pointermove', (event) => {
            if (!dragState || event.pointerId !== dragState.pointerId) {
                return;
            }

            const nextPosition = clampLauncherPosition(
                event.clientX - dragState.offsetX,
                event.clientY - dragState.offsetY,
            );

            const deltaX = event.clientX - dragState.startX;
            const deltaY = event.clientY - dragState.startY;
            if (Math.hypot(deltaX, deltaY) > 8) {
                dragState.moved = true;
            }

            if (dragState.moved) {
                applyLauncherPosition(nextPosition);
            }
        });

        const finishLauncherDrag = (event) => {
            if (!dragState || event.pointerId !== dragState.pointerId) {
                return;
            }

            aiStylistLauncherWrap.classList.remove('is-dragging');
            aiStylistLauncher.releasePointerCapture?.(event.pointerId);

            const rect = aiStylistLauncherWrap.getBoundingClientRect();
            const finalPosition = clampLauncherPosition(rect.left, rect.top);
            applyLauncherPosition(finalPosition);
            window.localStorage.setItem(launcherStorageKey, JSON.stringify(finalPosition));

            const wasMoved = dragState.moved;
            dragState = null;

            if (wasMoved) {
                event.preventDefault();
                return;
            }

            setAiStylistState(true);
        };

        aiStylistLauncher.addEventListener('pointerup', finishLauncherDrag);
        aiStylistLauncher.addEventListener('pointercancel', finishLauncherDrag);

        window.addEventListener('resize', () => {
            if (isCompactViewport()) {
                aiStylistLauncherWrap.style.left = '';
                aiStylistLauncherWrap.style.top = '';
                aiStylistLauncherWrap.style.right = '';
                aiStylistLauncherWrap.style.bottom = '';
                return;
            }

            if (!aiStylistLauncherWrap.style.left || !aiStylistLauncherWrap.style.top) {
                return;
            }

            const rect = aiStylistLauncherWrap.getBoundingClientRect();
            applyLauncherPosition(clampLauncherPosition(rect.left, rect.top));
        });
    }

    if (recommendationPrompt && chatFillButtons.length) {
        chatFillButtons.forEach((button) => {
            button.addEventListener('click', () => {
                recommendationPrompt.value = button.dataset.chatFill || '';
                recommendationPrompt.focus();
            });
        });
    }

    if (recommendationForm && recommendationChatLog) {
        setAiStylistComposerState(false);

        const appendChatMessage = (role, title, body, html = null) => {
            const article = document.createElement('article');
            article.className = `ai-chat-message ${role === 'user' ? 'ai-chat-message-user' : 'ai-chat-message-assistant'}`;

            const label = role === 'user' ? 'You' : 'AI stylist';
            article.innerHTML = `
                <span class="mini-label">${label}</span>
                <h3>${title}</h3>
                ${html !== null ? html : `<p class="mb-0">${body}</p>`}
            `;

            recommendationChatLog.appendChild(article);
            recommendationChatLog.scrollTop = recommendationChatLog.scrollHeight;
            return article;
        };

        const escapeHtml = (value) => {
            const div = document.createElement('div');
            div.textContent = value;
            return div.innerHTML;
        };

        const buildOutfitCards = (result) => {
            const engineLabel = result.engine === 'openai'
                ? 'Generated with OpenAI styling.'
                : result.engine === 'ml_fallback'
                    ? 'Generated with the local ML recommender fallback.'
                    : '';

            const outfits = (result.outfits || []).map((outfit, index) => {
                const wearGuide = (outfit.wear_guide || []).map((line) => `<li>${escapeHtml(line)}</li>`).join('');
                const items = (outfit.items || []).map((item) => `
                    <div class="ai-chat-outfit-item">
                        <strong>${escapeHtml(item.name)}</strong>
                        <span>${escapeHtml(item.category)} / ${escapeHtml(item.color || 'No color')} / ${escapeHtml(item.occasion || 'Open styling')}</span>
                        <small>Wear count: ${Number(item.wear_count || 0)}</small>
                    </div>
                `).join('');
                const reasons = (outfit.reasons || []).map((reason) => `<li>${escapeHtml(reason)}</li>`).join('');

                return `
                    <section class="ai-chat-outfit-card">
                        <span class="mini-label">Look ${index + 1}</span>
                        <h4>${escapeHtml(outfit.title || `Outfit ${index + 1}`)}</h4>
                        ${outfit.summary ? `<p class="text-muted mb-3">${escapeHtml(outfit.summary)}</p>` : ''}
                        ${wearGuide ? `<div class="ai-chat-outfit-block"><h5>How to wear it</h5><ul class="mb-0">${wearGuide}</ul></div>` : ''}
                        ${items ? `<div class="ai-chat-outfit-block"><h5>Recommended pieces</h5><div class="ai-chat-outfit-grid">${items}</div></div>` : '<div class="ai-chat-outfit-block"><h5>Fit direction</h5><p class="mb-0 text-muted">This recommendation is text-based because the current wardrobe does not yet contain a complete matching set.</p></div>'}
                        ${reasons ? `<div class="ai-chat-outfit-block"><h5>Why it works</h5><ul class="mb-0">${reasons}</ul></div>` : ''}
                    </section>
                `;
            }).join('');

            return `
                <p class="text-muted mb-3">${escapeHtml(result.access || '')}</p>
                ${engineLabel ? `<p class="text-muted small mb-4">${escapeHtml(engineLabel)}</p>` : ''}
                ${outfits || '<p class="text-muted mb-0">No outfits matched your current wardrobe.</p>'}
            `;
        };

        recommendationForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const formData = new FormData(recommendationForm);
            const occasion = formData.get('occasion') || 'Any occasion';
            const season = formData.get('season') || 'Any season';
            const color = formData.get('color') || 'Open palette';
            const prompt = (formData.get('preferred_style') || '').toString().trim();

            const userSummary = `
                <p class="mb-2">${escapeHtml(prompt || 'Recommend an outfit from my wardrobe.')}</p>
                <div class="ai-chat-tags">
                    <span>${escapeHtml(occasion.toString())}</span>
                    <span>${escapeHtml(season.toString())}</span>
                    <span>${escapeHtml(color.toString())}</span>
                </div>
            `;
            appendChatMessage('user', 'Outfit request', '', userSummary);
            const loadingMessage = appendChatMessage('assistant', 'Building recommendations', 'Reviewing your wardrobe and composing outfit options...');
            setAiStylistComposerState(true);

            try {
                const rootPrefix = aiStylistPanel?.dataset.aiRootPrefix || '';
                const response = await fetch(`${rootPrefix}actions/get_recommendation.php`, {
                    method: 'POST',
                    body: formData,
                });

                const data = await response.json();
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Unable to generate recommendation.');
                }

                const result = data.result;
                loadingMessage.querySelector('h3').textContent = result.message || 'Recommendations ready';
                loadingMessage.innerHTML = `
                    <span class="mini-label">AI stylist</span>
                    <h3>${escapeHtml(result.message || 'Recommendations ready')}</h3>
                    ${buildOutfitCards(result)}
                `;
                recommendationChatLog.scrollTop = recommendationChatLog.scrollHeight;
            } catch (error) {
                loadingMessage.querySelector('h3').textContent = 'Request failed';
                loadingMessage.innerHTML = `
                    <span class="mini-label">AI stylist</span>
                    <h3>Request failed</h3>
                    <div class="alert alert-danger mb-0">${escapeHtml(error.message)}</div>
                `;
                setAiStylistComposerState(false);
            }
        });
    }
});
