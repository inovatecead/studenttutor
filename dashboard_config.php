<?php
/**
 * Dashboard Configuration and Settings
 *
 * @package    local_studenttutor
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/local/studenttutor/lib.php');

// Security checks
require_login();
$context = context_system::instance();
require_capability('local/studenttutor:manage', $context);

// Page setup
$PAGE->set_url('/local/studenttutor/dashboard_config.php');
$PAGE->set_context($context);
$PAGE->set_title('Dashboard Configuration');
$PAGE->set_heading('Dashboard Configuration');
$PAGE->set_pagelayout('admin');

$PAGE->requires->css('/local/studenttutor/dashboard/css/dashboard.css');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    $config = new stdClass();
    $config->refresh_interval = clean_param($_POST['refresh_interval'], PARAM_INT);
    $config->chart_animations = clean_param($_POST['chart_animations'], PARAM_BOOL);
    $config->real_time_updates = clean_param($_POST['real_time_updates'], PARAM_BOOL);
    $config->show_alerts = clean_param($_POST['show_alerts'], PARAM_BOOL);
    $config->max_recent_activities = clean_param($_POST['max_recent_activities'], PARAM_INT);
    $config->default_time_range = clean_param($_POST['default_time_range'], PARAM_INT);
    
    // Save configuration (implement as needed)
    set_config('dashboard_config', json_encode($config), 'local_studenttutor');
    
    redirect($PAGE->url, 'Configurações salvas com sucesso!', null, \core\output\notification::NOTIFY_SUCCESS);
}

// Get current configuration
$current_config = get_config('local_studenttutor', 'dashboard_config');
if ($current_config) {
    $current_config = json_decode($current_config);
} else {
    $current_config = (object) [
        'refresh_interval' => 300,
        'chart_animations' => true,
        'real_time_updates' => true,
        'show_alerts' => true,
        'max_recent_activities' => 10,
        'default_time_range' => 30
    ];
}

echo $OUTPUT->header();
?>

<div class="dashboard-config">
    <div class="config-header">
        <h1><i class="fa fa-cogs"></i> Configuração do Dashboard</h1>
        <p>Configure as opções de exibição e comportamento do dashboard</p>
    </div>
    
    <form method="post" class="config-form">
        <?php echo html_writer::input_hidden_params($PAGE->url); ?>
        <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
        
        <div class="config-section">
            <h3><i class="fa fa-clock"></i> Atualização de Dados</h3>
            
            <div class="form-group">
                <label for="refresh_interval">Intervalo de Atualização (segundos)</label>
                <select name="refresh_interval" id="refresh_interval" class="form-control">
                    <option value="60" <?php echo $current_config->refresh_interval == 60 ? 'selected' : ''; ?>>1 minuto</option>
                    <option value="300" <?php echo $current_config->refresh_interval == 300 ? 'selected' : ''; ?>>5 minutos</option>
                    <option value="600" <?php echo $current_config->refresh_interval == 600 ? 'selected' : ''; ?>>10 minutos</option>
                    <option value="1800" <?php echo $current_config->refresh_interval == 1800 ? 'selected' : ''; ?>>30 minutos</option>
                    <option value="0" <?php echo $current_config->refresh_interval == 0 ? 'selected' : ''; ?>>Desabilitado</option>
                </select>
                <small class="form-text text-muted">Frequência de atualização automática dos dados</small>
            </div>
            
            <div class="form-group">
                <label for="default_time_range">Período Padrão (dias)</label>
                <select name="default_time_range" id="default_time_range" class="form-control">
                    <option value="7" <?php echo $current_config->default_time_range == 7 ? 'selected' : ''; ?>>7 dias</option>
                    <option value="30" <?php echo $current_config->default_time_range == 30 ? 'selected' : ''; ?>>30 dias</option>
                    <option value="90" <?php echo $current_config->default_time_range == 90 ? 'selected' : ''; ?>>90 dias</option>
                    <option value="365" <?php echo $current_config->default_time_range == 365 ? 'selected' : ''; ?>>1 ano</option>
                </select>
                <small class="form-text text-muted">Período padrão para análise de dados</small>
            </div>
        </div>
        
        <div class="config-section">
            <h3><i class="fa fa-chart-bar"></i> Gráficos e Visualização</h3>
            
            <div class="form-check">
                <input type="checkbox" name="chart_animations" id="chart_animations" class="form-check-input" 
                       <?php echo $current_config->chart_animations ? 'checked' : ''; ?>>
                <label for="chart_animations" class="form-check-label">Habilitar animações nos gráficos</label>
                <small class="form-text text-muted">Animações tornam a interface mais atrativa, mas podem afetar performance</small>
            </div>
            
            <div class="form-check">
                <input type="checkbox" name="real_time_updates" id="real_time_updates" class="form-check-input"
                       <?php echo $current_config->real_time_updates ? 'checked' : ''; ?>>
                <label for="real_time_updates" class="form-check-label">Atualizações em tempo real</label>
                <small class="form-text text-muted">Atualiza automaticamente métricas e gráficos</small>
            </div>
        </div>
        
        <div class="config-section">
            <h3><i class="fa fa-bell"></i> Alertas e Notificações</h3>
            
            <div class="form-check">
                <input type="checkbox" name="show_alerts" id="show_alerts" class="form-check-input"
                       <?php echo $current_config->show_alerts ? 'checked' : ''; ?>>
                <label for="show_alerts" class="form-check-label">Exibir alertas no dashboard</label>
                <small class="form-text text-muted">Mostra alertas importantes como estudantes sem tutor</small>
            </div>
            
            <div class="form-group">
                <label for="max_recent_activities">Máximo de atividades recentes</label>
                <input type="number" name="max_recent_activities" id="max_recent_activities" 
                       class="form-control" min="5" max="50" 
                       value="<?php echo $current_config->max_recent_activities; ?>">
                <small class="form-text text-muted">Número de atividades exibidas no feed de atividades</small>
            </div>
        </div>
        
        <div class="config-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save"></i> Salvar Configurações
            </button>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> Voltar ao Dashboard
            </a>
        </div>
    </form>
</div>

<style>
.dashboard-config {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.config-header {
    text-align: center;
    margin-bottom: 30px;
    padding: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 8px;
}

.config-header h1 {
    margin: 0;
    font-size: 2rem;
}

.config-form {
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.config-section {
    margin-bottom: 30px;
    padding: 20px;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    background: #f8f9fa;
}

.config-section h3 {
    margin: 0 0 20px 0;
    color: #495057;
    font-weight: 600;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 5px;
    display: block;
}

.form-check {
    margin-bottom: 15px;
}

.form-check-label {
    font-weight: 500;
    margin-left: 5px;
}

.config-actions {
    text-align: center;
    padding-top: 20px;
    border-top: 1px solid #e9ecef;
}

.config-actions .btn {
    margin: 0 10px;
    padding: 10px 20px;
}
</style>

<?php
echo $OUTPUT->footer();
?>
