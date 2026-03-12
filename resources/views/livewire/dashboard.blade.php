<div class="page">
    <div class="page-header">
        <h1>Dashboard</h1>
        <p class="page-subtitle">{{ now()->locale('pt_BR')->isoFormat('dddd, DD [de] MMMM [de] YYYY') }}</p>
    </div>

    {{-- Alertas --}}
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value">{{ $stats['total'] }}</div>
            <div class="stat-label">Cateteres Ativos</div>
        </div>
        <div class="stat-card danger">
            <div class="stat-value">{{ $stats['overdue'] }}</div>
            <div class="stat-label">Vencidos</div>
        </div>
        <div class="stat-card warning">
            <div class="stat-value">{{ $stats['urgent'] }}</div>
            <div class="stat-label">Vencem Amanhã</div>
        </div>
        <div class="stat-card info">
            <div class="stat-value">{{ $stats['warning'] }}</div>
            <div class="stat-label">Vencem em 3 dias</div>
        </div>
    </div>

    {{-- Operacional + Qualidade --}}
    <div class="stats-grid">
        <div class="stat-card success">
            <div class="stat-value">{{ $extraStats['inserted_today'] }}</div>
            <div class="stat-label">Inseridos Hoje</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{{ $extraStats['removed_today'] }}</div>
            <div class="stat-label">Retirados Hoje</div>
        </div>
        <div class="stat-card">
            @if($extraStats['avg_permanence'] !== null)
                <div class="stat-value">{{ $extraStats['avg_permanence'] }}d</div>
            @else
                <div class="stat-value" style="font-size: 14px; color: var(--gray-400);">Insuficiente</div>
            @endif
            <div class="stat-label">Permanência Média</div>
        </div>
        <div class="stat-card">
            @if($extraStats['on_time_rate'] !== null)
                <div class="stat-value">{{ $extraStats['on_time_rate'] }}%</div>
            @else
                <div class="stat-value" style="font-size: 14px; color: var(--gray-400);">Insuficiente</div>
            @endif
            <div class="stat-label">Retiradas no Prazo</div>
        </div>
    </div>

    {{-- Distribuições --}}
    <div class="dashboard-two-col">
        <div class="section" x-data="{ chart: 'pie' }">
            <div class="section-header">
                <h2>Indicações (Ativos)</h2>
                <div style="display:flex; gap:2px;">
                    <button x-on:click="chart = 'pie'"
                        :class="chart === 'pie' ? 'btn btn-secondary btn-xs' : 'btn btn-ghost btn-xs'">
                        Pizza
                    </button>
                    <button x-on:click="chart = 'bar'"
                        :class="chart === 'bar' ? 'btn btn-secondary btn-xs' : 'btn btn-ghost btn-xs'">
                        Barras
                    </button>
                </div>
            </div>
            @if(count($indicationStats) === 0)
                <div class="empty-state">Nenhum dado.</div>
            @else
                @php
                    $pieColors = ['#1d6fd4', '#d97706', '#16a34a', '#9333ea', '#0891b2'];
                    $parts = [];
                    $cum = 0;
                    foreach ($indicationStats as $i => $item) {
                        $color = $pieColors[$i % count($pieColors)];
                        $end = $cum + $item['pct'];
                        $parts[] = "{$color} {$cum}% {$end}%";
                        $cum = $end;
                    }
                    $gradient = implode(', ', $parts);
                @endphp

                {{-- Pizza --}}
                <div x-show="chart === 'pie'" class="pie-wrap">
                    <div class="pie-circle" style="background: conic-gradient({{ $gradient }})"></div>
                    <div class="pie-legend">
                        @foreach($indicationStats as $i => $item)
                            <div class="pie-legend-item">
                                <span class="pie-dot" style="background: {{ $pieColors[$i % count($pieColors)] }}"></span>
                                <span class="pie-legend-label" title="{{ $item['indication'] ?: '—' }}">{{ $item['indication'] ?: '—' }}</span>
                                <span class="pie-legend-pct">{{ $item['pct'] }}%</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Barras --}}
                <div x-show="chart === 'bar'" class="indication-list">
                    @foreach($indicationStats as $item)
                        <div>
                            <div class="indication-label">
                                <span>{{ $item['indication'] ?: '—' }}</span>
                                <span>{{ $item['count'] }} ({{ $item['pct'] }}%)</span>
                            </div>
                            <div class="indication-bar-bg">
                                <div class="indication-bar-fill" style="width: {{ $item['pct'] }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="section">
            <div class="section-header">
                <h2>Tipo de Procedimento (Ativos)</h2>
            </div>
            @if(count($procedureStats) === 0)
                <div class="empty-state">Nenhum dado.</div>
            @else
                @php
                    $maxProc = max(array_column($procedureStats, 'count'));
                    $colorMap = ['ELETIVO' => 'eletivo', 'URGENCIA' => 'urgencia'];
                @endphp
                <div class="bar-chart">
                    @foreach($procedureStats as $item)
                        @php
                            $pct = $maxProc > 0 ? round($item['count'] / $maxProc * 100) : 0;
                            $colorClass = $colorMap[$item['procedure_type']] ?? 'default';
                        @endphp
                        <div class="bar-col">
                            <div class="bar-value">{{ $item['count'] }}</div>
                            <div class="bar-fill {{ $colorClass }}" style="height: {{ $pct }}%"></div>
                        </div>
                    @endforeach
                </div>
                <div style="display: flex; gap: 16px; margin-top: 4px;">
                    @foreach($procedureStats as $item)
                        <div class="bar-label" style="flex: 1;">{{ $item['procedure_type'] }}</div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>


{{-- Alertas de Retirada --}}
    <div class="section">
        <div class="section-header">
            <h2>Alertas de Retirada</h2>
            <a href="{{ route('catheters') }}" class="btn btn-ghost btn-sm">Ver todos</a>
        </div>
        @if(count($alerts) === 0)
            <div class="empty-state">Nenhum alerta no momento.</div>
        @else
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Paciente</th>
                            <th>Prontuário</th>
                            <th>Indicação</th>
                            <th>Prazo Máx.</th>
                            <th>Dias Restantes</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($alerts as $r)
                            @php
                                $labels = ['overdue' => 'Vencido', 'urgent' => 'Urgente', 'warning' => 'Atenção', 'ok' => 'OK'];
                            @endphp
                            <tr>
                                <td>{{ $r['patient_name'] }}</td>
                                <td>{{ $r['record_number'] }}</td>
                                <td>{{ $r['indication'] }}</td>
                                <td>{{ \Carbon\Carbon::parse($r['max_removal_date'])->format('d/m/Y') }}</td>
                                <td class="{{ $r['days_left'] <= 0 ? 'text-danger' : '' }}">
                                    {{ $r['days_left'] <= 0 ? 'VENCIDO' : $r['days_left'].'d' }}
                                </td>
                                <td><span class="alert-badge {{ $r['alert_level'] }}">{{ $labels[$r['alert_level']] }}</span></td>
                                <td class="actions-cell">
                                    <a href="{{ route('patients.show', $r['patient_id']) }}" class="btn btn-secondary btn-xs">Ver</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
