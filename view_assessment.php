<?php
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';

// Proteksi halaman
if ($_SESSION['user_role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$student_id = $_SESSION['user_id'];
$assessment_data = null;

try {
    // Ambil data penilaian untuk siswa yang sedang login
    $stmt = $pdo->prepare("
        SELECT
            a.discipline_score, a.skill_score, a.teamwork_score, a.diligence_score, a.feedback, a.assessment_date,
            i.name as instructor_name
        FROM internship_assessments a
        JOIN instructors i ON a.instructor_id = i.id
        WHERE a.student_id = :student_id
    ");
    $stmt->execute([':student_id' => $student_id]);
    $assessment_data = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: Could not fetch assessment data. " . $e->getMessage());
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Hasil Penilaian Observasi</h1>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h6 class="m-0 font-weight-bold"><i class="fas fa-chart-pie me-2"></i>Grafik Penilaian</h6>
                </div>
                <div class="card-body">
                    <?php if ($assessment_data): ?>
                        <canvas id="assessmentRadarChart"></canvas>
                    <?php else: ?>
                        <div class="text-center p-5">
                            <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                            <p class="lead">Anda belum menerima penilaian dari instruktur.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header bg-info text-white">
                    <h6 class="m-0 font-weight-bold"><i class="fas fa-comment-alt me-2"></i>Feedback dari Instruktur</h6>
                </div>
                <div class="card-body">
                    <?php if ($assessment_data): ?>
                        <p><strong>Dinilai oleh:</strong> <?php echo htmlspecialchars($assessment_data['instructor_name']); ?></p>
                        <p><strong>Tanggal:</strong> <?php echo date('d M Y', strtotime($assessment_data['assessment_date'])); ?></p>
                        <hr>
                        <p class="fst-italic">"<?php echo nl2br(htmlspecialchars($assessment_data['feedback'] ?? 'Tidak ada feedback tambahan.')); ?>"</p>
                    <?php else: ?>
                        <p>Belum ada feedback yang tersedia.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<?php if ($assessment_data): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('assessmentRadarChart').getContext('2d');

    const data = {
        labels: [
            'Kedisiplinan',
            'Keahlian (Skill)',
            'Kerja Tim',
            'Kerajinan'
        ],
        datasets: [{
            label: 'Skor Penilaian',
            data: [
                <?php echo $assessment_data['discipline_score']; ?>,
                <?php echo $assessment_data['skill_score']; ?>,
                <?php echo $assessment_data['teamwork_score']; ?>,
                <?php echo $assessment_data['diligence_score']; ?>
            ],
            fill: true,
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            borderColor: 'rgb(54, 162, 235)',
            pointBackgroundColor: 'rgb(54, 162, 235)',
            pointBorderColor: '#fff',
            pointHoverBackgroundColor: '#fff',
            pointHoverBorderColor: 'rgb(54, 162, 235)'
        }]
    };

    const config = {
        type: 'radar',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                r: {
                    angleLines: {
                        display: false
                    },
                    suggestedMin: 0,
                    suggestedMax: 100,
                    pointLabels: {
                        font: {
                            size: 14
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.r !== null) {
                                label += context.parsed.r;
                            }
                            return label;
                        }
                    }
                }
            }
        }
    };

    new Chart(ctx, config);
});
</script>
<?php endif; ?>

<?php
require_once __DIR__ . '/templates/footer.php';
?>