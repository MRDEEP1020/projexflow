<x-slot name="header">
    @if($org)
        {{ $org->name }}
    @else
        Personal Workspace
    @endif
</x-slot>

<div class="dashboard">

    {{-- ── Stats row ──────────────────────────────────── --}}
    <div class="stats-grid">

        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(59,130,246,.1);color:#60a5fa">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-value">{{ $activeProjectsCount }}</span>
                <span class="stat-label">Active projects</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(245,158,11,.1);color:#fbbf24">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-value">{{ $tasksDueTodayCount }}</span>
                <span class="stat-label">Due today</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(126,232,162,.08);color:var(--accent)">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-value">{{ $teamMembersCount }}</span>
                <span class="stat-label">Team members</span>
            </div>
        </div>

        <div class="stat-card {{ $overdueTasksCount > 0 ? 'stat-danger' : '' }}">
            <div class="stat-icon" style="background:rgba(239,68,68,.1);color:#f87171">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-value" style="{{ $overdueTasksCount > 0 ? 'color:#f87171' : '' }}">{{ $overdueTasksCount }}</span>
                <span class="stat-label">Overdue tasks</span>
            </div>
        </div>

    </div>

    {{-- ── Main content ────────────────────────────────── --}}
    <div class="dashboard-body">

        {{-- Projects section --}}
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">Recent Projects</h2>
                <div class="section-actions">
                    <a href="{{ route('backend.projectList') }}" wire:navigate class="btn-ghost">
                        View all
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                    <a href="{{ route('backend.projectCreate') }}" wire:navigate class="btn-accent">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        New project
                    </a>
                </div>
            </div>

            @if($recentProjects->isEmpty())
                {{-- Empty state --}}
                <div class="empty-state">
                    <div class="empty-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--muted)" stroke-width="1.5" stroke-linecap="round"><path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/></svg>
                    </div>
                    <h3 class="empty-title">No projects yet</h3>
                    <p class="empty-sub">Create your first project to get started.</p>
                    <a href="{{ route('backend.projectCreate') }}" wire:navigate class="btn-accent" style="margin-top:4px">
                        Create project
                    </a>
                </div>
            @else
                <div class="projects-grid">
                    @foreach($recentProjects as $project)
                        <a href="{{ route('backend.projectBoard', $project) }}" wire:navigate class="project-card">

                            {{-- Status + priority --}}
                            <div class="project-card-top">
                                <span class="status-badge status-{{ $project->status }}">{{ ucfirst($project->status) }}</span>
                                <span class="priority-dot priority-{{ $project->priority }}" title="{{ ucfirst($project->priority) }} priority"></span>
                            </div>

                            {{-- Name --}}
                            <h3 class="project-card-name">{{ $project->name }}</h3>

                            {{-- Progress bar --}}
                            <div class="progress-wrap">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width:{{ $project->progress_percentage }}%"></div>
                                </div>
                                <span class="progress-pct">{{ $project->progress_percentage }}%</span>
                            </div>

                            {{-- Footer: due date + member count --}}
                            <div class="project-card-footer">
                                @if($project->due_date)
                                    <span class="project-due {{ $project->isOverdue() ? 'is-overdue' : '' }}">
                                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                        {{ $project->due_date->format('M d') }}
                                    </span>
                                @endif
                                @if($project->github_repo)
                                    <span class="project-github">
                                        <svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z"/></svg>
                                    </span>
                                @endif
                            </div>

                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Due today section --}}
        @if($tasksDueToday->isNotEmpty())
            <div class="section" style="margin-top:32px">
                <div class="section-header">
                    <h2 class="section-title">
                        Due Today
                        <span class="title-badge">{{ $tasksDueTodayCount }}</span>
                    </h2>
                    <a href="{{ route('my-tasks') }}" wire:navigate class="btn-ghost">View all tasks</a>
                </div>

                <div class="task-list">
                    @foreach($tasksDueToday as $task)
                        <div class="task-row">
                            <div class="task-status-dot status-{{ $task->status }}"></div>
                            <div class="task-info">
                                <span class="task-title">{{ $task->title }}</span>
                                <span class="task-project">{{ $task->project->name ?? 'Personal' }}</span>
                            </div>
                            <span class="task-priority priority-badge-{{ $task->priority }}">{{ ucfirst($task->priority) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

    </div>

</div>

<style>
.dashboard { max-width: 1200px; }

/* Stats */
.stats-grid { display:grid;grid-template-columns:repeat(2,1fr);gap:12px;margin-bottom:28px; }
@media(min-width:768px) { .stats-grid { grid-template-columns:repeat(4,1fr); } }
.stat-card { display:flex;align-items:center;gap:14px;background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:16px; }
.stat-card.stat-danger { border-color:rgba(239,68,68,.2); }
.stat-icon { width:40px;height:40px;flex-shrink:0;border-radius:10px;display:flex;align-items:center;justify-content:center; }
.stat-body { display:flex;flex-direction:column;gap:2px; }
.stat-value { font-family:'Syne',sans-serif;font-size:22px;font-weight:700;color:#fff;line-height:1; }
.stat-label { font-family:'Inter',sans-serif;font-size:12px;color:var(--muted); }

/* Section */
.section-header { display:flex;align-items:center;justify-content:space-between;margin-bottom:16px; }
.section-title { font-family:'Syne',sans-serif;font-size:16px;font-weight:700;color:#fff;display:flex;align-items:center;gap:8px; }
.title-badge { font-family:'DM Mono',monospace;font-size:11px;background:rgba(126,232,162,.1);color:var(--accent);border:1px solid rgba(126,232,162,.2);border-radius:6px;padding:1px 7px; }
.section-actions { display:flex;align-items:center;gap:8px; }
.btn-ghost { display:flex;align-items:center;gap:6px;padding:7px 12px;background:none;border:1px solid var(--border);border-radius:8px;font-family:'Inter',sans-serif;font-size:12.5px;color:var(--dim);text-decoration:none;transition:all .15s; }
.btn-ghost:hover { border-color:var(--border2);color:var(--text); }
.btn-accent { display:flex;align-items:center;gap:6px;padding:7px 13px;background:var(--accent);border:none;border-radius:8px;font-family:'Syne',sans-serif;font-size:12.5px;font-weight:600;color:#080c14;text-decoration:none;cursor:pointer;transition:all .15s; }
.btn-accent:hover { background:var(--accent2);transform:translateY(-1px); }

/* Projects grid */
.projects-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:12px; }
.project-card { background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:16px;text-decoration:none;display:flex;flex-direction:column;gap:12px;transition:all .2s; }
.project-card:hover { border-color:var(--border2);transform:translateY(-2px);box-shadow:0 8px 24px rgba(0,0,0,.2); }
.project-card-top { display:flex;align-items:center;justify-content:space-between; }
.status-badge { font-family:'DM Mono',monospace;font-size:10.5px;padding:2px 8px;border-radius:6px;text-transform:capitalize; }
.status-planning { background:rgba(59,130,246,.1);color:#60a5fa; }
.status-active { background:rgba(16,185,129,.1);color:#34d399; }
.status-on_hold { background:rgba(245,158,11,.1);color:#fbbf24; }
.status-completed { background:rgba(126,232,162,.1);color:var(--accent); }
.status-cancelled { background:rgba(239,68,68,.1);color:#f87171; }
.priority-dot { width:8px;height:8px;border-radius:50%; }
.priority-low { background:#60a5fa; }
.priority-medium { background:#fbbf24; }
.priority-high { background:#f97316; }
.priority-critical { background:#ef4444; }
.project-card-name { font-family:'Syne',sans-serif;font-size:14px;font-weight:600;color:#fff;margin:0; }
.progress-wrap { display:flex;align-items:center;gap:10px; }
.progress-bar { flex:1;height:4px;background:var(--border);border-radius:4px;overflow:hidden; }
.progress-fill { height:100%;background:var(--accent);border-radius:4px;transition:width .5s ease; }
.progress-pct { font-family:'DM Mono',monospace;font-size:10.5px;color:var(--muted);flex-shrink:0; }
.project-card-footer { display:flex;align-items:center;gap:10px; }
.project-due { display:flex;align-items:center;gap:4px;font-family:'Inter',sans-serif;font-size:11.5px;color:var(--muted); }
.project-due.is-overdue { color:#f87171; }
.project-github { color:var(--muted); }

/* Empty state */
.empty-state { display:flex;flex-direction:column;align-items:center;gap:12px;padding:48px 24px;background:var(--surface);border:1px dashed var(--border);border-radius:14px;text-align:center; }
.empty-icon { width:56px;height:56px;background:var(--surface2);border-radius:14px;display:flex;align-items:center;justify-content:center; }
.empty-title { font-family:'Syne',sans-serif;font-size:16px;font-weight:600;color:#fff;margin:0; }
.empty-sub { font-family:'Inter',sans-serif;font-size:13.5px;color:var(--dim);margin:0; }

/* Task list */
.task-list { display:flex;flex-direction:column;gap:4px; }
.task-row { display:flex;align-items:center;gap:12px;padding:10px 14px;background:var(--surface);border:1px solid var(--border);border-radius:9px;transition:border-color .15s; }
.task-row:hover { border-color:var(--border2); }
.task-status-dot { width:8px;height:8px;border-radius:50%;flex-shrink:0; }
.status-todo { background:var(--border2); }
.status-in_progress { background:#60a5fa; }
.status-in_review { background:#fbbf24; }
.status-done { background:var(--accent); }
.status-blocked { background:#f87171; }
.task-info { flex:1;min-width:0; }
.task-title { display:block;font-family:'Inter',sans-serif;font-size:13.5px;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
.task-project { display:block;font-family:'Inter',sans-serif;font-size:11.5px;color:var(--muted);margin-top:1px; }
.priority-badge-low { color:#60a5fa;font-family:'DM Mono',monospace;font-size:11px; }
.priority-badge-medium { color:#fbbf24;font-family:'DM Mono',monospace;font-size:11px; }
.priority-badge-high { color:#f97316;font-family:'DM Mono',monospace;font-size:11px; }
.priority-badge-critical { color:#ef4444;font-family:'DM Mono',monospace;font-size:11px; }
</style>
