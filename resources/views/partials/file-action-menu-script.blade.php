@once
    @push('scripts')
        <script>
            (() => {
                if (window.__fileActionMenuBound) return;
                window.__fileActionMenuBound = true;

                document.addEventListener('click', (event) => {
                    const trigger = event.target.closest('.action-menu-trigger');

                    if (trigger) {
                        const currentMenu = trigger.closest('.file-action-menu');

                        document.querySelectorAll('.file-action-menu[open]').forEach((menu) => {
                            if (menu !== currentMenu) {
                                menu.removeAttribute('open');
                            }
                        });

                        requestAnimationFrame(() => {
                            if (currentMenu?.open) {
                                positionActionPanel(currentMenu, trigger);
                            }
                        });

                        return;
                    }

                    const close = event.target.closest('[data-action-close]');

                    if (close) {
                        event.preventDefault();
                        close.closest('.file-action-menu')?.removeAttribute('open');
                        return;
                    }

                    if (! event.target.closest('.file-action-menu')) {
                        document.querySelectorAll('.file-action-menu[open]').forEach((menu) => {
                            menu.removeAttribute('open');
                        });
                    }
                });

                document.addEventListener('pointerdown', (event) => {
                    const handle = event.target.closest('[data-action-drag-handle]');

                    if (! handle || event.target.closest('[data-action-close]')) {
                        return;
                    }

                    const panel = handle.closest('.file-action-panel');
                    if (! panel) return;

                    event.preventDefault();

                    const rect = panel.getBoundingClientRect();
                    const offsetX = event.clientX - rect.left;
                    const offsetY = event.clientY - rect.top;

                    const move = (e) => {
                        e.preventDefault();
                        setPanelPosition(panel, e.clientX - offsetX, e.clientY - offsetY);
                    };

                    const stop = () => {
                        document.removeEventListener('pointermove', move);
                        document.removeEventListener('pointerup', stop);
                        document.removeEventListener('pointercancel', stop);
                    };

                    document.addEventListener('pointermove', move);
                    document.addEventListener('pointerup', stop, { once: true });
                    document.addEventListener('pointercancel', stop, { once: true });
                });

                function positionActionPanel(menu, trigger) {
                    const panel = menu.querySelector('.file-action-panel');
                    if (! panel || ! trigger) return;

                    panel.style.removeProperty('--action-panel-left');
                    panel.style.removeProperty('--action-panel-top');

                    const triggerRect = trigger.getBoundingClientRect();
                    const panelRect = panel.getBoundingClientRect();
                    const gap = 8;
                    let left = triggerRect.right - panelRect.width;
                    let top = triggerRect.bottom + gap;

                    if (top + panelRect.height > window.innerHeight - gap) {
                        top = triggerRect.top - panelRect.height - gap;
                    }

                    setPanelPosition(panel, left, top);
                }

                function setPanelPosition(panel, left, top) {
                    const margin = 12;
                    const rect = panel.getBoundingClientRect();
                    const width = rect.width || Math.min(390, window.innerWidth - margin * 2);
                    const height = rect.height || Math.min(560, window.innerHeight - margin * 2);
                    const maxLeft = Math.max(margin, window.innerWidth - width - margin);
                    const maxTop = Math.max(margin, window.innerHeight - height - margin);

                    panel.style.setProperty('--action-panel-left', `${Math.min(Math.max(left, margin), maxLeft)}px`);
                    panel.style.setProperty('--action-panel-top', `${Math.min(Math.max(top, margin), maxTop)}px`);
                }
            })();
        </script>
    @endpush
@endonce
