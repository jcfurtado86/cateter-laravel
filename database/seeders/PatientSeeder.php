<?php

namespace Database\Seeders;

use App\Models\CatheterRecord;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PatientSeeder extends Seeder
{
    private array $nomes = [
        'Maria Silva', 'João Oliveira', 'Ana Santos', 'Carlos Souza', 'Fernanda Lima',
        'Roberto Costa', 'Juliana Pereira', 'Marcos Almeida', 'Patrícia Ferreira', 'Eduardo Rodrigues',
        'Luciana Gomes', 'André Martins', 'Camila Barbosa', 'Ricardo Ribeiro', 'Isabela Carvalho',
        'Felipe Araujo', 'Beatriz Nascimento', 'Gustavo Monteiro', 'Larissa Cunha', 'Paulo Mendes',
        'Renata Cavalcante', 'Thiago Correia', 'Aline Pinto', 'Diego Melo', 'Vanessa Cardoso',
        'Bruno Teixeira', 'Simone Castro', 'Leandro Vieira', 'Mariana Moreira', 'Rodrigo Nunes',
    ];

    private array $indicacoes = [
        'Retenção urinária aguda', 'Pós-operatório de prostatectomia', 'Bexiga neurogênica',
        'Monitoramento de diurese em UTI', 'Obstrução uretral', 'Incontinência urinária grave',
        'Pós-cirurgia pélvica', 'Estenose uretral', 'Trauma abdominal', 'Insuficiência renal aguda',
    ];

    private array $calibres = ['12Fr', '14Fr', '16Fr', '18Fr', '20Fr', '22Fr'];
    private array $passagens = ['Sonda Foley simples', 'Sonda de três vias', 'Cateterismo intermitente', 'Sonda coudé'];

    public function run(): void
    {
        $adminUser = User::where('role', 'ADMIN')->first();
        $doctorUser = User::where('role', 'DOCTOR')->first();
        $users = [$adminUser->id, $doctorUser->id];

        foreach ($this->nomes as $i => $nome) {
            $sexo = $i % 3 === 0 ? 'M' : ($i % 3 === 1 ? 'F' : ($i % 7 === 0 ? 'OUTRO' : 'F'));
            $racas = ['BRANCA', 'PARDA', 'PRETA', 'AMARELA', 'INDIGENA', 'NAO_INFORMADA'];
            $nascimento = Carbon::now()->subYears(rand(35, 82))->subDays(rand(0, 365));

            $patient = Patient::create([
                'full_name'      => $nome,
                'record_number'  => 'PRT' . str_pad($i + 1001, 5, '0', STR_PAD_LEFT),
                'birth_date'     => $nascimento->format('Y-m-d'),
                'sex'            => $sexo,
                'race'           => $racas[array_rand($racas)],
                'phone'          => '(11) 9' . rand(1000, 9999) . '-' . rand(1000, 9999),
                'active'         => true,
                'created_by_id'  => $users[array_rand($users)],
            ]);

            // Cada paciente tem histórico de 1-3 cateteres
            $numCateteres = rand(1, 3);
            $insertionBase = Carbon::now()->subDays(rand(60, 180));

            for ($c = 0; $c < $numCateteres; $c++) {
                $isLast = ($c === $numCateteres - 1);
                $insertionDate = $insertionBase->copy()->addDays($c * rand(20, 40));
                $minDays = rand(5, 10);
                $maxDays = $minDays + rand(5, 14);

                // Determina o estado do cateter baseado no índice do paciente
                $daysOffset = match(true) {
                    $isLast && $i < 5  => $maxDays + rand(1, 5),   // vencidos
                    $isLast && $i < 12 => $maxDays - 1,              // urgente (1 dia)
                    $isLast && $i < 20 => $maxDays - rand(2, 3),     // atenção (3 dias)
                    $isLast            => $maxDays - rand(10, 20),   // ok
                    default            => $maxDays + rand(5, 30),    // histórico (já retirados)
                };

                $insertionDate = Carbon::now()->subDays($daysOffset)->setTime(rand(6, 22), rand(0, 59));
                $minRemoval = $insertionDate->copy()->addDays($minDays);
                $maxRemoval = $insertionDate->copy()->addDays($maxDays);
                $removedAt = (!$isLast || $numCateteres > 1 && !$isLast)
                    ? $maxRemoval->copy()->addDays(rand(1, 5))
                    : null;

                // Último cateter dos primeiros 30 permanece ativo
                if ($isLast && $i < 30) {
                    $removedAt = null;
                }

                CatheterRecord::create([
                    'patient_id'            => $patient->id,
                    'created_by_id'         => $users[array_rand($users)],
                    'had_previous_catheter' => $c > 0,
                    'insertion_date'        => $insertionDate,
                    'procedure_type'        => rand(0, 1) ? 'ELETIVO' : 'URGENCIA',
                    'indication'            => $this->indicacoes[array_rand($this->indicacoes)],
                    'caliber'               => $this->calibres[array_rand($this->calibres)],
                    'insertion_side'        => rand(0, 1) ? 'DIREITO' : 'ESQUERDO',
                    'passage_type'          => $this->passagens[array_rand($this->passagens)],
                    'safety_wire'           => (bool) rand(0, 1),
                    'min_days'              => $minDays,
                    'max_days'              => $maxDays,
                    'min_removal_date'      => $minRemoval,
                    'max_removal_date'      => $maxRemoval,
                    'removed_at'            => $removedAt,
                    'removed_by_id'         => $removedAt ? $users[array_rand($users)] : null,
                ]);
            }
        }
    }
}
