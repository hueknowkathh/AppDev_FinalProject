<?php
$isPageDir = str_contains($_SERVER['PHP_SELF'], '/pages/');
$rootPrefix = $isPageDir ? '../' : '';
$jsRelativePath = 'assets/js/script.js';
$jsVersionPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $jsRelativePath);
$jsVersion = is_file($jsVersionPath) ? filemtime($jsVersionPath) : time();
?>
        </main>
    </div>
    <div class="ai-stylist-launcher-wrap">
        <button
            type="button"
            class="ai-stylist-launcher"
            data-ai-stylist-launcher
            aria-controls="aiStylistPanel"
            aria-expanded="false"
        >
            <span class="ai-stylist-launcher-mark">AI</span>
            <span class="ai-stylist-launcher-copy">
                <strong>AI Stylist</strong>
                <small>Outfit chat</small>
            </span>
        </button>
    </div>
    <div class="ai-stylist-backdrop" data-ai-stylist-backdrop hidden>
        <aside class="ai-stylist-panel" id="aiStylistPanel" role="dialog" aria-modal="true" aria-labelledby="aiStylistTitle" data-ai-root-prefix="<?= htmlspecialchars($rootPrefix) ?>">
            <div class="ai-stylist-panel-header">
                <div>
                    <span class="mini-label">AI stylist</span>
                    <h2 id="aiStylistTitle">Outfit chat</h2>
                    <p class="text-muted mb-0">Describe the look you want.</p>
                </div>
                <div class="ai-stylist-panel-actions">
                    <button type="button" class="ai-stylist-reset" data-ai-stylist-reset hidden>New request</button>
                    <button
                        type="button"
                        class="ai-stylist-close"
                        data-ai-stylist-close
                        aria-label="Close AI stylist"
                        onclick="(function(btn){const backdrop=btn.closest('[data-ai-stylist-backdrop]');if(backdrop){backdrop.hidden=true;backdrop.style.display='none';backdrop.classList.remove('is-visible');document.body.classList.remove('ai-stylist-open');document.querySelectorAll('[aria-controls=&quot;aiStylistPanel&quot;]').forEach(function(el){el.setAttribute('aria-expanded','false');});}})(this)"
                    >&times;</button>
                </div>
            </div>

            <div class="ai-stylist-body">
                <div id="aiStylistChatLog" class="ai-chat-log" aria-live="polite">
                    <article class="ai-chat-message ai-chat-message-assistant">
                        <span class="mini-label">AI stylist</span>
                        <h3>Ready</h3>
                        <p class="mb-0">Pick the occasion and tell me the style direction.</p>
                    </article>
                </div>

                <div class="ai-stylist-composer" data-ai-stylist-composer>
                    <div class="ai-chat-suggestions">
                        <button type="button" class="ai-chat-suggestion" data-chat-fill="Build a smart casual campus look with neutral colors.">Smart casual</button>
                        <button type="button" class="ai-chat-suggestion" data-chat-fill="Recommend a formal outfit with a clean black palette.">Formal black</button>
                        <button type="button" class="ai-chat-suggestion" data-chat-fill="Create a casual streetwear outfit for everyday use.">Streetwear</button>
                    </div>

                    <form id="aiStylistForm" class="ai-chat-form">
                        <div class="ai-chat-controls">
                            <div>
                                <label for="aiOccasion" class="form-label">Occasion</label>
                                <select class="form-select" id="aiOccasion" name="occasion" required>
                                    <option value="">Choose...</option>
                                    <option>Casual</option>
                                    <option>Formal</option>
                                    <option>Party</option>
                                    <option>Business</option>
                                    <option>Travel</option>
                                    <option>Sportswear</option>
                                </select>
                            </div>
                            <div>
                                <label for="aiSeason" class="form-label">Season</label>
                                <select class="form-select" id="aiSeason" name="season">
                                    <option value="">Any season</option>
                                    <option>All Season</option>
                                    <option>Summer</option>
                                    <option>Rainy</option>
                                    <option>Winter</option>
                                    <option>Spring</option>
                                    <option>Autumn</option>
                                </select>
                            </div>
                            <div>
                                <label for="aiColor" class="form-label">Color palette</label>
                                <input type="text" class="form-control" id="aiColor" name="color" placeholder="Black, beige, white, navy">
                            </div>
                        </div>

                        <div class="ai-chat-composer">
                            <div class="ai-chat-input-wrap">
                                <label for="aiPrompt" class="form-label">Style prompt</label>
                                <textarea
                                    class="form-control ai-chat-input"
                                    id="aiPrompt"
                                    name="preferred_style"
                                    rows="3"
                                    placeholder="Smart casual for campus with a minimal silhouette."
                                ></textarea>
                                <p class="ai-chat-help text-muted mb-0">Mention the vibe or silhouette.</p>
                            </div>
                            <button type="submit" class="btn btn-gold ai-chat-submit">Ask AI Stylist</button>
                        </div>
                    </form>
                </div>
            </div>
        </aside>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= htmlspecialchars($rootPrefix . $jsRelativePath . '?v=' . $jsVersion) ?>"></script>
</body>
</html>
