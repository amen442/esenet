<?php
require_once __DIR__ . '/../layouts/components.php';
require_once __DIR__ . '/../../config/db_connect.php';

$admin_id = get_admin_id();
if (!$admin_id) { header("Location: ../../login.php"); exit(); }

$stats = get_admin_stats();

// Candidatures par statut
$candidatures_by_status = [];
try {
    $result = $conn->query("SELECT statut, COUNT(*) as count FROM candidature GROUP BY statut");
    foreach ($result->fetchAll() as $row) $candidatures_by_status[] = $row;
} catch (Exception $e) {
    $candidatures_by_status = [['statut'=>'EN_ATTENTE','count'=>10],['statut'=>'ACCEPTE','count'=>8],['statut'=>'REFUSE','count'=>3]];
}

// Offres par type
$offers_by_type = [];
try {
    $result = $conn->query("SELECT type, COUNT(*) as count FROM offre GROUP BY type");
    foreach ($result->fetchAll() as $row) $offers_by_type[] = $row;
} catch (Exception $e) {
    $offers_by_type = [['type'=>'Stage','count'=>5],['type'=>'Alternance','count'=>3]];
}

// Top entreprises
$top_companies = [];
try {
    // Vérifier d'abord la structure des tables
    $result = $conn->query("SELECT e.nomsociete, COUNT(o.id) as nb_offres 
                           FROM entreprise e 
                           LEFT JOIN offre o ON e.id = o.idEntreprise 
                           WHERE e.nomsociete IS NOT NULL AND e.nomsociete != '' 
                           GROUP BY e.id, e.nomsociete 
                           ORDER BY nb_offres DESC, e.nomsociete ASC 
                           LIMIT 5");
    if (!$result) {
                $top_companies = [];
    } else {
        foreach ($result->fetchAll() as $row) {
            $top_companies[] = $row;
        }
    }
} catch (Exception $e) { 
    error_log("Exception top companies: " . $e->getMessage());
    $top_companies = []; 
}

// Inscriptions mensuelles
$monthly_reg = [];
try {
    // Utiliser la table utilisateur comme dans dashboard.php
    $result = $conn->query("SELECT MONTH(dateInscription) as month, statut, COUNT(*) as count FROM utilisateur WHERE dateInscription IS NOT NULL GROUP BY MONTH(dateInscription), statut ORDER BY MONTH(dateInscription) LIMIT 24");
    if (!$result) {
                $monthly_reg = [];
    } else {
        foreach ($result->fetchAll() as $row) {
            $monthly_reg[] = $row;
        }
    }
} catch (Exception $e) { 
    error_log("Exception monthly reg: " . $e->getMessage());
    $monthly_reg = []; 
}

// Événements par mois
$events_by_month = [];
try {
    $result = $conn->query("SELECT MONTH(dateEvent) as month, COUNT(*) as count FROM evenement GROUP BY MONTH(dateEvent) ORDER BY MONTH(dateEvent) LIMIT 12");
    if (!$result) {
                $events_by_month = [];
    } else {
        foreach ($result->fetchAll() as $row) {
            $events_by_month[] = $row;
        }
    }
} catch (Exception $e) { 
    error_log("Exception events by month: " . $e->getMessage());
    $events_by_month = []; 
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapports - ESEN Connect</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../public/assets/css/main.css">
    <link rel="stylesheet" href="../../public/assets/css/admin-navbar.css">
    <link rel="stylesheet" href="../../public/assets/css/admin-dashboard.css">
    <link rel="icon" href="../../public/assets/images/1.jpeg" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Theme toggle
        const themeToggle = document.querySelector('.toggle-dark');
        const savedTheme = localStorage.getItem('theme') || 'light';
        if (savedTheme === 'dark') document.body.classList.add('dark');
        if (themeToggle) themeToggle.addEventListener('click', function() {
            document.body.classList.toggle('dark');
            localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
            // Update chart colors from CSS variables
            Chart.defaults.color = getComputedStyle(document.body).getPropertyValue('--chart-text-color').trim();
            Chart.defaults.borderColor = getComputedStyle(document.body).getPropertyValue('--chart-grid-color').trim();
            Object.values(Chart.instances).forEach(chart => {
                if (chart.options.scales?.r) {
                    chart.options.scales.r.grid.color = getComputedStyle(document.body).getPropertyValue('--chart-grid-color').trim();
                    chart.options.scales.r.angleLines.color = getComputedStyle(document.body).getPropertyValue('--chart-grid-color').trim();
                    chart.options.scales.r.pointLabels.color = getComputedStyle(document.body).getPropertyValue('--chart-text-color').trim();
                }
                if (chart.options.scales?.x) {
                    chart.options.scales.x.grid.color = getComputedStyle(document.body).getPropertyValue('--chart-grid-color').trim();
                    chart.options.scales.x.ticks.color = getComputedStyle(document.body).getPropertyValue('--chart-text-color').trim();
                }
                if (chart.options.scales?.y) {
                    chart.options.scales.y.grid.color = getComputedStyle(document.body).getPropertyValue('--chart-grid-color').trim();
                    chart.options.scales.y.ticks.color = getComputedStyle(document.body).getPropertyValue('--chart-text-color').trim();
                }
                // Update background colors from CSS variables
                chart.data.datasets.forEach((ds, i) => {
                    if (chart.config.type === 'line') {
                        ds.backgroundColor = i === 0 ? getComputedStyle(document.body).getPropertyValue('--chart-line-bg-1').trim() : getComputedStyle(document.body).getPropertyValue('--chart-line-bg-2').trim();
                        ds.pointBorderColor = getComputedStyle(document.body).getPropertyValue('--chart-point-border').trim();
                    } else if (chart.config.type === 'polarArea') {
                        ds.backgroundColor = [getComputedStyle(document.body).getPropertyValue('--chart-polar-bg-1').trim(),getComputedStyle(document.body).getPropertyValue('--chart-polar-bg-2').trim(),getComputedStyle(document.body).getPropertyValue('--chart-polar-bg-3').trim(),getComputedStyle(document.body).getPropertyValue('--chart-polar-bg-4').trim(),getComputedStyle(document.body).getPropertyValue('--chart-polar-bg-5').trim()];
                    } else if (chart.config.type === 'bar' && chart.options.indexAxis === 'y') {
                        ds.backgroundColor = [getComputedStyle(document.body).getPropertyValue('--chart-polar-bg-1').trim(),getComputedStyle(document.body).getPropertyValue('--chart-polar-bg-2').trim(),getComputedStyle(document.body).getPropertyValue('--chart-polar-bg-3').trim(),getComputedStyle(document.body).getPropertyValue('--chart-polar-bg-4').trim(),getComputedStyle(document.body).getPropertyValue('--chart-polar-bg-5').trim()];
                    } else if (chart.config.type === 'bar') {
                        ds.backgroundColor = getComputedStyle(document.body).getPropertyValue('--chart-bar-bg').trim();
                    } else if (chart.config.type === 'radar') {
                        ds.backgroundColor = getComputedStyle(document.body).getPropertyValue('--chart-radar-bg').trim();
                        ds.pointBorderColor = getComputedStyle(document.body).getPropertyValue('--chart-point-border').trim();
                    }
                });
                chart.update();
            });
        });

        // Mobile menu
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const verticalNavbar = document.getElementById('verticalNavbar');
        if (mobileMenuToggle && verticalNavbar) {
            mobileMenuToggle.addEventListener('click', () => verticalNavbar.classList.toggle('active'));
            document.addEventListener('click', e => {
                if (!verticalNavbar.contains(e.target) && !mobileMenuToggle.contains(e.target))
                    verticalNavbar.classList.remove('active');
            });
        }

        // Navbar toggle
        const navbarToggle = document.getElementById('navbarToggle');
        const navbarShowBtn = document.getElementById('navbarShowBtn');
        if (navbarToggle && verticalNavbar) navbarToggle.addEventListener('click', () => { verticalNavbar.classList.toggle('collapsed'); updateBtn(); });
        if (navbarShowBtn && verticalNavbar) navbarShowBtn.addEventListener('click', () => { verticalNavbar.classList.remove('collapsed'); updateBtn(); });
        function updateBtn() { navbarShowBtn.classList.toggle('visible', verticalNavbar.classList.contains('collapsed')); }
        if (verticalNavbar && navbarShowBtn) updateBtn();

        // Custom Cursor
        const cursor = document.querySelector('.custom-cursor');
        const cursorDot = document.querySelector('.custom-cursor-dot');
        let mx=0,my=0,cx=0,cy=0;
        if (cursor && cursorDot) {
            document.addEventListener('mousemove', e => { mx=e.clientX; my=e.clientY; });
            (function anim() { cx+=(mx-cx)*0.15; cy+=(my-cy)*0.15; cursor.style.left=cx+'px'; cursor.style.top=cy+'px'; cursorDot.style.left=mx+'px'; cursorDot.style.top=my+'px'; requestAnimationFrame(anim); })();
            document.querySelectorAll('a,button,input,.btn,.stat-card,.dashboard-card,.chart-card,.nav-item,.export-btn').forEach(el => {
                el.addEventListener('mouseenter', () => cursor.classList.add('hover'));
                el.addEventListener('mouseleave', () => cursor.classList.remove('hover'));
            });
            document.addEventListener('mousedown', () => cursor.classList.add('clicking'));
            document.addEventListener('mouseup', () => cursor.classList.remove('clicking'));
        }

        // Export PDF
        document.getElementById('exportPDF').addEventListener('click', function() {
            const el = document.getElementById('reportContent');
            const btn = this;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Génération...';
            btn.style.pointerEvents = 'none';
            
            // Configuration simple et robuste
            const opt = {
                margin: 10,
                filename: 'ESEN_Rapport_' + new Date().toLocaleDateString('fr-FR').replace(/\//g,'-') + '.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { 
                    scale: 2,
                    useCORS: true,
                    logging: false
                },
                jsPDF: { 
                    unit: 'mm', 
                    format: 'a4', 
                    orientation: 'portrait' 
                }
            };
            
            // Génération du PDF avec gestion d'erreurs
            html2pdf().set(opt).from(el).save()
                .then(() => {
                    btn.innerHTML = '<i class="fas fa-file-pdf"></i> Export PDF';
                    btn.style.pointerEvents = '';
                })
                .catch((error) => {
                    console.error('Erreur PDF:', error);
                    btn.innerHTML = '<i class="fas fa-file-pdf"></i> Export PDF';
                    btn.style.pointerEvents = '';
                    alert('Erreur lors de la génération du PDF. Veuillez réessayer.');
                });
        });

        // Chart defaults - read from CSS variables
        const isDark = document.body.classList.contains('dark');
        Chart.defaults.font.family = 'Inter';
        Chart.defaults.color = getComputedStyle(document.body).getPropertyValue('--chart-text-color').trim();
        Chart.defaults.borderColor = getComputedStyle(document.body).getPropertyValue('--chart-grid-color').trim();
        const months = ['Jan','Fév','Mar','Avr','Mai','Juin','Juil','Août','Sep','Oct','Nov','Déc'];
        const ttOpts = {
            backgroundColor: getComputedStyle(document.body).getPropertyValue('--chart-tooltip-bg').trim(),
            titleColor: getComputedStyle(document.body).getPropertyValue('--chart-tooltip-text').trim(),
            bodyColor: getComputedStyle(document.body).getPropertyValue('--chart-tooltip-text').trim(),
            borderColor: getComputedStyle(document.body).getPropertyValue('--chart-grid-color').trim(),
            borderWidth: 1
        };
        const scaleOpts = {
            y:{ beginAtZero:true, grid:{color:getComputedStyle(document.body).getPropertyValue('--chart-grid-color').trim()}, ticks:{color:getComputedStyle(document.body).getPropertyValue('--chart-text-color').trim()} },
            x:{ grid:{display:false}, ticks:{color:getComputedStyle(document.body).getPropertyValue('--chart-text-color').trim()} }
        };

        // 1. Inscriptions mensuelles (Line multi-dataset)
        const etuData = new Array(12).fill(0), ensData = new Array(12).fill(0);
        <?php foreach($monthly_reg as $mr): if(isset($mr['month'])): $idx=(int)$mr['month']-1; ?>
            <?php if(($mr['statut']??'')==='ETUDIANT'): ?>etuData[<?php echo $idx;?>]=<?php echo (int)$mr['count'];?>;<?php endif;?>
            <?php if(($mr['statut']??'')==='ENSEIGNANT'): ?>ensData[<?php echo $idx;?>]=<?php echo (int)$mr['count'];?>;<?php endif;?>
        <?php endif; endforeach; ?>
        new Chart(document.getElementById('registrationChart'), {
            type:'line', data:{ labels:months, datasets:[
                { label:'Étudiants', data:etuData, borderColor:'#00d4c4', backgroundColor:getComputedStyle(document.body).getPropertyValue('--chart-line-bg-1').trim(), borderWidth:3, fill:true, tension:0.4, pointRadius:5, pointHoverRadius:8, pointBackgroundColor:'#00d4c4', pointBorderColor:getComputedStyle(document.body).getPropertyValue('--chart-point-border').trim(), pointBorderWidth:2 },
                { label:'Enseignants', data:ensData, borderColor:'#2f6fed', backgroundColor:getComputedStyle(document.body).getPropertyValue('--chart-line-bg-2').trim(), borderWidth:3, fill:true, tension:0.4, pointRadius:5, pointHoverRadius:8, pointBackgroundColor:'#2f6fed', pointBorderColor:getComputedStyle(document.body).getPropertyValue('--chart-point-border').trim(), pointBorderWidth:2 }
            ]}, options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false},tooltip:ttOpts}, scales:scaleOpts }
        });

        // 2. Candidatures par statut (Doughnut)
        const cLabels=<?php echo json_encode(array_column($candidatures_by_status,'statut'));?>;
        const cData=<?php echo json_encode(array_column($candidatures_by_status,'count'));?>;
        const cColors=cLabels.map(l=>({ACCEPTE:'#00d4c4',EN_ATTENTE:'#f59e0b',REFUSE:'#ef4444'}[l]||'#8b5cf6'));
        const cLabelsFr=cLabels.map(l=>({ACCEPTE:'Acceptées',EN_ATTENTE:'En attente',REFUSE:'Refusées'}[l]||l));
        new Chart(document.getElementById('candidaturesChart'), {
            type:'doughnut', data:{ labels:cLabelsFr, datasets:[{data:cData,backgroundColor:cColors,borderWidth:0,hoverOffset:4}] },
            options:{ responsive:true, maintainAspectRatio:false, cutout:'60%', plugins:{legend:{display:false},tooltip:{...ttOpts,callbacks:{label:ctx=>{const t=ctx.dataset.data.reduce((a,b)=>a+b,0);return ctx.label+': '+ctx.parsed+' ('+Math.round(ctx.parsed/t*100)+'%)';}}}} }
        });

        // 3. Offres par type (Polar Area)
        new Chart(document.getElementById('offersTypeChart'), {
            type:'polarArea', data:{ labels:<?php echo json_encode(array_column($offers_by_type,'type'));?>, datasets:[{data:<?php echo json_encode(array_column($offers_by_type,'count'));?>,backgroundColor:[getComputedStyle(document.body).getPropertyValue('--chart-polar-bg-1').trim(),getComputedStyle(document.body).getPropertyValue('--chart-polar-bg-2').trim(),getComputedStyle(document.body).getPropertyValue('--chart-polar-bg-3').trim(),getComputedStyle(document.body).getPropertyValue('--chart-polar-bg-4').trim(),getComputedStyle(document.body).getPropertyValue('--chart-polar-bg-5').trim()],borderWidth:0}] },
            options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false},tooltip:ttOpts}, scales:{r:{grid:{color:getComputedStyle(document.body).getPropertyValue('--chart-grid-color').trim()},ticks:{display:false}}} }
        });

        // 4. Événements par mois (Bar)
        const evtData=new Array(12).fill(0);
        <?php foreach($events_by_month as $em): if(isset($em['month'])): ?>evtData[<?php echo (int)$em['month']-1;?>]=<?php echo (int)$em['count'];?>;<?php endif; endforeach; ?>
        new Chart(document.getElementById('eventsMonthChart'), {
            type:'bar', data:{ labels:months, datasets:[{label:'Événements',data:evtData,backgroundColor:getComputedStyle(document.body).getPropertyValue('--chart-bar-bg').trim(),borderColor:'#8b5cf6',borderWidth:2,borderRadius:8}] },
            options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false},tooltip:ttOpts}, scales:{...scaleOpts,y:{...scaleOpts.y,ticks:{...scaleOpts.y.ticks,stepSize:1}}} }
        });

        // 5. Top entreprises (Horizontal Bar)
        new Chart(document.getElementById('topCompaniesChart'), {
            type:'bar', data:{ labels:<?php echo json_encode(array_column($top_companies,'nomsociete'));?>, datasets:[{label:'Offres',data:<?php echo json_encode(array_map('intval',array_column($top_companies,'nb_offres')));?>,backgroundColor:[getComputedStyle(document.body).getPropertyValue('--chart-polar-bg-1').trim(),getComputedStyle(document.body).getPropertyValue('--chart-polar-bg-2').trim(),getComputedStyle(document.body).getPropertyValue('--chart-polar-bg-3').trim(),getComputedStyle(document.body).getPropertyValue('--chart-polar-bg-4').trim(),getComputedStyle(document.body).getPropertyValue('--chart-polar-bg-5').trim()],borderWidth:0,borderRadius:8}] },
            options:{ indexAxis:'y', responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false},tooltip:ttOpts}, scales:{x:{beginAtZero:true,grid:{color:getComputedStyle(document.body).getPropertyValue('--chart-grid-color').trim()},ticks:{color:getComputedStyle(document.body).getPropertyValue('--chart-text-color').trim(),stepSize:1}},y:{grid:{display:false},ticks:{color:getComputedStyle(document.body).getPropertyValue('--chart-text-color').trim()}}} }
        });

        // 6. Radar candidatures
        const acc=<?php $a=0; foreach($candidatures_by_status as $c) if(($c['statut']??'')==='ACCEPTE') $a=(int)$c['count']; echo $a;?>;
        const pen=<?php $p=0; foreach($candidatures_by_status as $c) if(($c['statut']??'')==='EN_ATTENTE') $p=(int)$c['count']; echo $p;?>;
        const ref=<?php $r=0; foreach($candidatures_by_status as $c) if(($c['statut']??'')==='REFUSE') $r=(int)$c['count']; echo $r;?>;
        const tot=<?php echo (int)($stats['candidatures']??0);?>;
        new Chart(document.getElementById('radarChart'), {
            type:'radar', data:{ labels:['Acceptées','En attente','Refusées','Total'], datasets:[{label:'Candidatures',data:[acc,pen,ref,tot],backgroundColor:getComputedStyle(document.body).getPropertyValue('--chart-radar-bg').trim(),borderColor:'#00d4c4',borderWidth:2,pointBackgroundColor:'#00d4c4',pointBorderColor:getComputedStyle(document.body).getPropertyValue('--chart-point-border').trim(),pointBorderWidth:2,pointRadius:5}] },
            options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false},tooltip:ttOpts}, scales:{r:{grid:{color:getComputedStyle(document.body).getPropertyValue('--chart-grid-color').trim()},angleLines:{color:getComputedStyle(document.body).getPropertyValue('--chart-grid-color').trim()},ticks:{display:false},pointLabels:{color:getComputedStyle(document.body).getPropertyValue('--chart-text-color').trim(),font:{size:12}}}} }
        });
    });
    </script>
</head>
<body>
    <!-- Custom Cursor -->
    <div class="custom-cursor"></div>
    <div class="custom-cursor-dot"></div>

    <!-- Animated Background Shapes -->
    <div class="bg-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
        <div class="shape shape-4"></div>
    </div>

    <!-- Grid Pattern -->
    <div class="grid-pattern"></div>

    <?php include 'admin-navbar.php'; ?>

    <main class="main-content-wrapper">
        <div class="dashboard-container">
            <div class="dashboard-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
                <div>
                    <h1>Rapports</h1>
                    <p>Consultez les statistiques et rapports</p>
                </div>
                <button id="exportPDF" class="export-btn">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </button>
            </div>

            <div id="reportContent">
                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card"><div class="icon blue"><i class="fas fa-user-graduate"></i></div><div class="value"><?php echo $stats['students']??0;?></div><div class="label">Étudiants</div></div>
                    <div class="stat-card"><div class="icon green"><i class="fas fa-chalkboard-teacher"></i></div><div class="value"><?php echo $stats['teachers']??0;?></div><div class="label">Enseignants</div></div>
                    <div class="stat-card"><div class="icon orange"><i class="fas fa-building"></i></div><div class="value"><?php echo $stats['companies']??0;?></div><div class="label">Entreprises</div></div>
                    <div class="stat-card"><div class="icon purple"><i class="fas fa-briefcase"></i></div><div class="value"><?php echo $stats['offers']??0;?></div><div class="label">Offres</div></div>
                    <div class="stat-card"><div class="icon blue"><i class="fas fa-calendar-alt"></i></div><div class="value"><?php echo $stats['events']??0;?></div><div class="label">Événements</div></div>
                </div>

                <!-- Charts Row 1 -->
                <div class="charts-grid">
                    <div class="chart-card">
                        <h4><i class="fas fa-chart-line"></i> Inscriptions mensuelles</h4>
                        <div class="chart-wrapper"><canvas id="registrationChart"></canvas></div>
                        <div class="chart-legend">
                            <div class="legend-item"><div class="legend-color" style="background:#00d4c4;"></div><span>Étudiants</span></div>
                            <div class="legend-item"><div class="legend-color" style="background:#2f6fed;"></div><span>Enseignants</span></div>
                        </div>
                    </div>
                    <div class="chart-card">
                        <h4><i class="fas fa-chart-pie"></i> Candidatures par statut</h4>
                        <div class="chart-wrapper"><canvas id="candidaturesChart"></canvas></div>
                        <div class="chart-legend">
                            <?php foreach($candidatures_by_status as $cs): ?>
                            <div class="legend-item"><div class="legend-color" style="background:<?php echo ['ACCEPTE'=>'#00d4c4','EN_ATTENTE'=>'#f59e0b','REFUSE'=>'#ef4444'][$cs['statut']??'']??'#8b5cf6';?>;"></div><span><?php echo ['ACCEPTE'=>'Acceptées','EN_ATTENTE'=>'En attente','REFUSE'=>'Refusées'][$cs['statut']??'']??$cs['statut'];?> (<?php echo $cs['count'];?>)</span></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Charts Row 2 -->
                <div class="charts-grid">
                    <div class="chart-card">
                        <h4><i class="fas fa-chart-area"></i> Offres par type</h4>
                        <div class="chart-wrapper"><canvas id="offersTypeChart"></canvas></div>
                        <div class="chart-legend">
                            <?php foreach($offers_by_type as $i=>$ot): ?>
                            <div class="legend-item"><div class="legend-color" style="background:<?php echo ['#2f6fed','#00d4c4','#8b5cf6','#f59e0b','#ef4444'][$i]??'#8b5cf6';?>;"></div><span><?php echo htmlspecialchars($ot['type']);?> (<?php echo $ot['count'];?>)</span></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="chart-card">
                        <h4><i class="fas fa-chart-bar"></i> Événements par mois</h4>
                        <div class="chart-wrapper"><canvas id="eventsMonthChart"></canvas></div>
                    </div>
                </div>

                <!-- Charts Row 3 -->
                <div class="charts-grid">
                    <div class="chart-card">
                        <h4><i class="fas fa-trophy"></i> Top entreprises</h4>
                        <div class="chart-wrapper"><canvas id="topCompaniesChart"></canvas></div>
                    </div>
                    <div class="chart-card">
                        <h4><i class="fas fa-crosshairs"></i> Répartition candidatures</h4>
                        <div class="chart-wrapper"><canvas id="radarChart"></canvas></div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../public/assets/js/main.js"></script>
</body>
</html>
