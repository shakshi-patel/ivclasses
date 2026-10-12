@php
    $mh = $moduleHelpRuntime ?? null;
@endphp
@if (! empty($mh['guide_id']))
    <script type="application/json" id="moduleHelpBoot">@json($mh)</script>

    <div class="modal fade admin-modal" id="moduleHelpWizardModal" tabindex="-1" aria-labelledby="moduleHelpWizardLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content module-help-wizard-modal">
                <div class="modal-header">
                    <div>
                        <div class="text-muted small mb-1" id="mhwModuleLabel">Module</div>
                        <h5 class="modal-title mb-0" id="moduleHelpWizardLabel">Guide title</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="module-help-wizard-intro text-muted mb-3" id="mhwIntro"></p>

                    <div class="module-help-wizard-progress mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small fw-semibold" style="color: var(--iv-primary-dark);">
                                Step <span id="mhwStepCurrent">1</span> of <span id="mhwStepTotal">1</span>
                            </span>
                            <span class="admin-status-pill is-navy" id="mhwMediaType">None</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar" id="mhwProgressBar" role="progressbar" style="width: 0%; background: var(--iv-primary);"></div>
                        </div>
                    </div>

                    <div class="module-help-wizard-media mb-3 d-none" id="mhwMedia">
                        <div class="module-help-media-placeholder d-none" id="mhwMediaPlaceholder">
                            <i class="bx bx-image-alt" id="mhwMediaIcon"></i>
                            <span id="mhwMediaLabel">No media</span>
                        </div>
                        <div class="module-help-media-file d-none" id="mhwMediaFileWrap">
                            <img id="mhwMediaImage" class="d-none" alt="Step media">
                            <video id="mhwMediaVideo" class="d-none" controls></video>
                        </div>
                        <div class="module-help-media-embed d-none" id="mhwMediaEmbedWrap">
                            <iframe id="mhwMediaEmbed" src="" title="Guide media" allowfullscreen loading="lazy"></iframe>
                        </div>
                    </div>
                    <div class="text-muted small mb-3 d-none" id="mhwCaption"></div>

                    <h6 class="fw-bold mb-2" style="color: var(--iv-primary-dark);" id="mhwStepTitle">Step title</h6>
                    <p class="mb-0" id="mhwStepDesc" style="color: #3d4a45; line-height: 1.55;"></p>
                </div>
                <div class="modal-footer justify-content-between gap-2">
                    <button type="button" class="btn btn-outline-secondary" id="mhwPrevBtn">
                        <i class="bx bx-left-arrow-alt me-1"></i> Previous
                    </button>
                    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-end">
                        <span class="d-none" id="mhwCtaWrap">
                            <a href="#" class="btn btn-outline-primary" id="mhwCta">
                                <i class="bx bx-link-external me-1"></i>
                                <span id="mhwCtaLabel">Open</span>
                            </a>
                        </span>
                        <button type="button" class="btn admin-btn-primary" id="mhwNextBtn">
                            Next <i class="bx bx-right-arrow-alt ms-1"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .module-help-how-btn {
            white-space: nowrap;
        }
        .module-help-wizard-modal .modal-body { padding-top: 1rem; }
        .module-help-wizard-intro { font-size: 0.92rem; }
        .module-help-wizard-media {
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid rgba(var(--iv-primary-rgb), 0.14);
            background: #f3faf7;
            min-height: 180px;
        }
        .module-help-media-placeholder {
            min-height: 180px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            color: var(--iv-primary-dark);
            padding: 1.5rem;
            text-align: center;
        }
        .module-help-media-placeholder i { font-size: 2.4rem; opacity: 0.85; }
        .module-help-media-file img,
        .module-help-media-file video {
            display: block;
            width: 100%;
            max-height: 320px;
            object-fit: contain;
            background: #0f1c19;
        }
        .module-help-media-embed {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            background: #0f1c19;
        }
        .module-help-media-embed iframe {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            border: 0;
        }
    </style>
@endif
