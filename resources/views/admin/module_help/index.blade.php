@extends('admin.layouts.layout')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y admin-list-page">
        <div class="admin-main-card admin-list-toolbar">
            <div class="admin-main-card-inner">
                <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-3">
                    <div>
                        <h4 class="admin-page-title">
                            <i class="bx bx-help-circle"></i>
                            Module Help
                        </h4>
                        <p class="text-muted small mb-0">Guides shown to institute roles when they open a CRM module.</p>
                    </div>
                    <a href="{{ route('admin.module_help.create') }}" class="btn admin-btn-primary">
                        <i class="bx bx-plus me-1"></i> Add Guide
                    </a>
                </div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Module Help</li>
                    </ol>
                </nav>

            </div>
        </div>

        <div class="admin-main-card admin-list-table-card">
            <div class="admin-main-card-inner">
                <div class="admin-table-wrap">
                    <div class="admin-list-card-bar">
                        <h5>Guides List</h5>
                    </div>
                    <table class="table datatables-basic admin-list-table align-middle mb-0" style="min-width: 1000px;">
                            <thead>
                                <tr>
                                    <th style="width: 60px;">Sr</th>
                                    <th>Module</th>
                                    <th>Guide Title</th>
                                    <th>Roles</th>
                                    <th class="text-center" style="width: 90px;">Steps</th>
                                    <th class="text-center" style="width: 90px;">Sort</th>
                                    <th style="width: 110px;">Status</th>
                                    <th class="text-center" style="width: 140px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($guides as $i => $guide)
                                    <tr data-id="{{ $guide->id }}">
                                        <td>{{ $i + 1 }}</td>
                                        <td>
                                            <span class="fw-semibold">{{ $guide->moduleLabel() }}</span>
                                            <div class="text-muted small"><code>{{ $guide->module_key }}</code></div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ $guide->title }}</div>
                                            @if ($guide->intro)
                                                <div class="text-muted small">{{ \Illuminate\Support\Str::limit($guide->intro, 70) }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1">
                                                @foreach ($guide->roleNames() as $role)
                                                    <span class="admin-status-pill is-navy">{{ \Illuminate\Support\Str::headline($role) }}</span>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="text-center">{{ $guide->steps_count }}</td>
                                        <td class="text-center">{{ $guide->sort_order }}</td>
                                        <td>
                                            <span class="admin-status-pill {{ $guide->status === 'active' ? 'is-active' : 'is-muted' }}">
                                                {{ ucfirst($guide->status) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <button type="button"
                                                class="admin-action-icon btn-module-help-view"
                                                title="View steps"
                                                data-guide-id="{{ $guide->id }}">
                                                <i class="bx bx-show"></i>
                                            </button>
                                            <a href="{{ route('admin.module_help.edit', $guide) }}" class="admin-action-icon" title="Edit">
                                                <i class="bx bx-pencil"></i>
                                            </a>
                                            <button type="button"
                                                class="admin-action-icon btn-module-help-del"
                                                title="Delete"
                                                data-url="{{ route('admin.module_help.destroy', $guide) }}">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">No guides yet. Click Add Guide to create one.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade admin-modal" id="moduleHelpViewModal" tabindex="-1" aria-labelledby="moduleHelpViewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content module-help-view-modal">
                <div class="modal-header">
                    <div>
                        <div class="text-muted small mb-1" id="mhvModuleLabel">Module</div>
                        <h5 class="modal-title mb-0" id="moduleHelpViewModalLabel">Guide title</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="module-help-view-intro text-muted mb-3" id="mhvIntro"></p>
                    <div class="module-help-view-roles mb-3" id="mhvRoles"></div>

                    <div class="module-help-view-progress mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small fw-semibold" style="color: var(--iv-primary-dark);">
                                Step <span id="mhvStepCurrent">1</span> of <span id="mhvStepTotal">1</span>
                            </span>
                            <span class="admin-status-pill is-navy" id="mhvMediaType">None</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar" id="mhvProgressBar" role="progressbar" style="width: 0%; background: var(--iv-primary);"></div>
                        </div>
                    </div>

                    <div class="module-help-view-media mb-3 d-none" id="mhvMedia">
                        <div class="module-help-media-placeholder d-none" id="mhvMediaPlaceholder">
                            <i class="bx bx-image-alt" id="mhvMediaIcon"></i>
                            <span id="mhvMediaLabel">No media</span>
                        </div>
                        <div class="module-help-media-file d-none" id="mhvMediaFileWrap">
                            <img id="mhvMediaImage" class="d-none" alt="Step media">
                            <video id="mhvMediaVideo" class="d-none" controls></video>
                        </div>
                        <div class="module-help-media-embed d-none" id="mhvMediaEmbedWrap">
                            <iframe id="mhvMediaEmbed" src="" title="Embed preview" allowfullscreen loading="lazy"></iframe>
                        </div>
                    </div>
                    <div class="text-muted small mb-3 d-none" id="mhvCaption"></div>

                    <h6 class="fw-bold mb-2" style="color: var(--iv-primary-dark);" id="mhvStepTitle">Step title</h6>
                    <p class="mb-0" id="mhvStepDesc" style="color: #3d4a45; line-height: 1.55;"></p>
                </div>
                <div class="modal-footer justify-content-between gap-2">
                    <button type="button" class="btn btn-outline-secondary" id="mhvPrevBtn">
                        <i class="bx bx-left-arrow-alt me-1"></i> Previous
                    </button>
                    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-end">
                        <span class="d-none" id="mhvCtaWrap">
                            <a href="#" class="btn btn-outline-primary" id="mhvCta">
                                <i class="bx bx-link-external me-1"></i>
                                <span id="mhvCtaLabel">Open</span>
                            </a>
                        </span>
                        <button type="button" class="btn admin-btn-primary" id="mhvNextBtn">
                            Next <i class="bx bx-right-arrow-alt ms-1"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .module-help-view-modal .modal-body { padding-top: 1rem; }
        .module-help-view-intro { font-size: 0.92rem; }
        .module-help-view-media {
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
        .module-help-view-roles .admin-status-pill { margin: 0 4px 4px 0; }
    </style>
@endpush

@push('scripts')
    <script>
        (function() {
            const guides = @json($guidesPreview);
            const modalEl = document.getElementById('moduleHelpViewModal');
            if (!modalEl || typeof bootstrap === 'undefined') return;

            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            let steps = [];
            let stepIndex = 0;

            const els = {
                moduleLabel: document.getElementById('mhvModuleLabel'),
                title: document.getElementById('moduleHelpViewModalLabel'),
                intro: document.getElementById('mhvIntro'),
                roles: document.getElementById('mhvRoles'),
                stepCurrent: document.getElementById('mhvStepCurrent'),
                stepTotal: document.getElementById('mhvStepTotal'),
                mediaType: document.getElementById('mhvMediaType'),
                progress: document.getElementById('mhvProgressBar'),
                media: document.getElementById('mhvMedia'),
                placeholder: document.getElementById('mhvMediaPlaceholder'),
                mediaIcon: document.getElementById('mhvMediaIcon'),
                mediaLabel: document.getElementById('mhvMediaLabel'),
                fileWrap: document.getElementById('mhvMediaFileWrap'),
                image: document.getElementById('mhvMediaImage'),
                video: document.getElementById('mhvMediaVideo'),
                embedWrap: document.getElementById('mhvMediaEmbedWrap'),
                embed: document.getElementById('mhvMediaEmbed'),
                caption: document.getElementById('mhvCaption'),
                stepTitle: document.getElementById('mhvStepTitle'),
                stepDesc: document.getElementById('mhvStepDesc'),
                ctaWrap: document.getElementById('mhvCtaWrap'),
                cta: document.getElementById('mhvCta'),
                ctaLabel: document.getElementById('mhvCtaLabel'),
                prev: document.getElementById('mhvPrevBtn'),
                next: document.getElementById('mhvNextBtn'),
            };

            function headline(str) {
                return String(str || '')
                    .replace(/_/g, ' ')
                    .replace(/\b\w/g, function(c) { return c.toUpperCase(); });
            }

            function clearMedia() {
                els.image.classList.add('d-none');
                els.image.removeAttribute('src');
                els.video.classList.add('d-none');
                els.video.removeAttribute('src');
                els.video.load?.();
                els.embed.src = '';
                els.placeholder.classList.add('d-none');
                els.fileWrap.classList.add('d-none');
                els.embedWrap.classList.add('d-none');
                els.media.classList.add('d-none');
            }

            function renderStep() {
                const total = steps.length || 1;
                const step = steps[stepIndex] || {};
                const type = step.media_type || 'none';

                els.stepCurrent.textContent = String(stepIndex + 1);
                els.stepTotal.textContent = String(total);
                els.progress.style.width = Math.round(((stepIndex + 1) / total) * 100) + '%';
                els.mediaType.textContent = headline(type);
                els.stepTitle.textContent = step.title || '—';
                els.stepDesc.textContent = step.description || '';
                els.caption.textContent = step.media_caption || '';
                els.caption.classList.toggle('d-none', !step.media_caption);

                clearMedia();

                if (type === 'embed' && step.embed_url) {
                    els.media.classList.remove('d-none');
                    els.embedWrap.classList.remove('d-none');
                    els.embed.src = step.embed_url;
                } else if (type === 'image' && step.media_url) {
                    els.media.classList.remove('d-none');
                    els.fileWrap.classList.remove('d-none');
                    els.image.classList.remove('d-none');
                    els.image.src = step.media_url;
                } else if (type === 'video' && step.media_url) {
                    els.media.classList.remove('d-none');
                    els.fileWrap.classList.remove('d-none');
                    els.video.classList.remove('d-none');
                    els.video.src = step.media_url;
                } else if (type !== 'none') {
                    els.media.classList.remove('d-none');
                    els.placeholder.classList.remove('d-none');
                    els.mediaIcon.className = 'bx ' + (type === 'video' ? 'bx-video' : 'bx-image-alt');
                    els.mediaLabel.textContent = 'Media file not uploaded yet';
                }

                els.prev.disabled = stepIndex <= 0;
                const isLast = stepIndex >= total - 1;
                els.next.innerHTML = isLast
                    ? 'Done <i class="bx bx-check ms-1"></i>'
                    : 'Next <i class="bx bx-right-arrow-alt ms-1"></i>';

                const ctaLabel = step.cta_label || '';
                const ctaUrl = step.cta_url || '';
                const hasCta = !!(ctaLabel && ctaUrl && els.ctaWrap && els.cta);
                if (els.ctaWrap) {
                    els.ctaWrap.classList.toggle('d-none', !hasCta);
                }
                if (hasCta) {
                    const href = resolveAppHref(ctaUrl);
                    els.cta.setAttribute('href', href);
                    const external = isExternalHref(href);
                    els.cta.setAttribute('target', external ? '_blank' : '_self');
                    if (external) {
                        els.cta.setAttribute('rel', 'noopener noreferrer');
                    } else {
                        els.cta.removeAttribute('rel');
                    }
                    if (els.ctaLabel) {
                        els.ctaLabel.textContent = ctaLabel;
                    } else {
                        els.cta.textContent = ctaLabel;
                    }
                }
            }

            function appBasePath() {
                const meta = document.querySelector('meta[name="app-base-path"]');
                return meta ? String(meta.getAttribute('content') || '').replace(/\/$/, '') : '';
            }

            function resolveAppHref(raw) {
                const url = String(raw || '').trim();
                if (!url) return '#';
                if (/^(https?:)?\/\//i.test(url)) {
                    try {
                        const abs = new URL(url, window.location.origin);
                        if (abs.origin === window.location.origin && abs.pathname.indexOf('/admin') === 0) {
                            return appBasePath() + abs.pathname + abs.search + abs.hash;
                        }
                    } catch (e) {}
                    return url;
                }
                const path = url.charAt(0) === '/' ? url : '/' + url;
                const base = appBasePath();
                if (base && path.indexOf(base + '/') !== 0 && path !== base) {
                    if (path.indexOf('/admin') === 0) {
                        return base + path;
                    }
                }
                return path;
            }

            function isExternalHref(href) {
                try {
                    const u = new URL(href, window.location.origin);
                    return u.origin !== window.location.origin;
                } catch (e) {
                    return /^(https?:)?\/\//i.test(String(href || ''));
                }
            }

            function openGuide(id) {
                const guide = guides[id] || guides[String(id)];
                if (!guide) return;

                steps = Array.isArray(guide.steps) ? guide.steps : [];
                stepIndex = 0;

                els.moduleLabel.textContent = (guide.module_label || guide.module_key || 'Module') + ' module';
                els.title.textContent = guide.title || 'Guide';
                els.intro.textContent = guide.intro || '';
                els.intro.classList.toggle('d-none', !guide.intro);

                els.roles.innerHTML = '';
                (guide.roles || []).forEach(function(role) {
                    const pill = document.createElement('span');
                    pill.className = 'admin-status-pill is-navy';
                    pill.textContent = headline(role);
                    els.roles.appendChild(pill);
                });

                renderStep();
                modal.show();
            }

            document.querySelectorAll('.btn-module-help-view').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    openGuide(btn.getAttribute('data-guide-id'));
                });
            });

            els.prev?.addEventListener('click', function() {
                if (stepIndex > 0) {
                    stepIndex -= 1;
                    renderStep();
                }
            });

            els.next?.addEventListener('click', function() {
                if (stepIndex < steps.length - 1) {
                    stepIndex += 1;
                    renderStep();
                } else {
                    modal.hide();
                }
            });

            modalEl.addEventListener('hidden.bs.modal', function() {
                clearMedia();
            });

            document.querySelectorAll('.btn-module-help-del').forEach(function(btn) {
                btn.addEventListener('click', async function() {
                    const ok = await AdminAjax.confirm({
                        title: 'Delete guide?',
                        text: 'This will remove the guide and all its steps.',
                        confirmText: 'Yes, delete'
                    });
                    if (!ok) return;
                    try {
                        await AdminAjax.destroy(btn.getAttribute('data-url'));
                        const tr = btn.closest('tr');
                        if (window.jQuery && jQuery.fn.DataTable && jQuery.fn.DataTable.isDataTable(jQuery('.admin-list-table'))) {
                            jQuery('.admin-list-table').DataTable().row(tr).remove().draw(false);
                        } else {
                            tr?.remove();
                        }
                        await AdminAjax.success('Guide deleted.');
                    } catch (e) {}
                });
            });
        })();
    </script>
@endpush
