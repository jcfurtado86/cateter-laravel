<div class="page">
    <div class="page-header">
        <h1>Histórico de Notificações</h1>
    </div>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Paciente</th>
                    <th>Prontuário</th>
                    <th>Telefone</th>
                    <th>Tipo</th>
                    <th>Status</th>
                    <th>Enviado por</th>
                    <th>Data/Hora</th>
                </tr>
            </thead>
            <tbody>
                @forelse($notifications as $n)
                    @php
                        $types = ['ALERT_3D' => '3 dias', 'ALERT_1D' => '1 dia', 'ALERT_DUE' => 'Vencimento', 'MANUAL' => 'Manual'];
                    @endphp
                    <tr>
                        <td>{{ $n->patient->full_name }}</td>
                        <td>{{ $n->patient->record_number }}</td>
                        <td>{{ $n->phone }}</td>
                        <td><span class="badge">{{ $types[$n->type] ?? $n->type }}</span></td>
                        <td>
                            <span class="badge {{ $n->status === 'SENT' ? 'badge-active' : 'badge-inactive' }}">
                                {{ $n->status === 'SENT' ? 'Enviado' : 'Falhou' }}
                            </span>
                        </td>
                        <td>{{ $n->sentBy?->name ?? 'Sistema' }}</td>
                        <td>{{ \Carbon\Carbon::parse($n->sent_at)->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center">Nenhuma notificação enviada</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $notifications->links() }}
</div>
