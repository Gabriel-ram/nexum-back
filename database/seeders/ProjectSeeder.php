<?php

namespace Database\Seeders;

use App\Models\Portfolio;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        // Helper: obtener skill IDs por nombre (ignora los que no existan)
        $skill = fn (array $names) => Skill::whereIn('name', $names)->pluck('id')->all();
        $cat   = fn (string $name) => ProjectCategory::where('name', $name)->value('id');

        // ─────────────────────────────────────────────────
        // Ana García — Full Stack Developer
        // ─────────────────────────────────────────────────
        $ana = User::where('email', 'ana.garcia@portfolio.test')->first();
        if ($ana && $ana->portfolio) {
            $projects = [
                [
                    'data' => [
                        'title'       => 'E-Commerce Platform',
                        'description' => 'Plataforma de comercio electrónico con carrito de compras, pasarela de pagos y panel de administración. Arquitectura monolítica con API REST para el frontend.',
                        'project_url' => 'https://github.com/anagarcia-dev/ecommerce-platform',
                        'category_id' => $cat('Web Development'),
                    ],
                    'skills' => $skill(['Laravel', 'React', 'PostgreSQL', 'Redis', 'Docker']),
                ],
                [
                    'data' => [
                        'title'       => 'Task Management App',
                        'description' => 'Aplicación de gestión de tareas con tableros Kanban, asignación de equipos y notificaciones en tiempo real. Inspirada en Trello.',
                        'project_url' => 'https://github.com/anagarcia-dev/taskboard',
                        'category_id' => $cat('Web Development'),
                    ],
                    'skills' => $skill(['Vue.js', 'Node.js', 'MongoDB', 'GitHub Actions']),
                ],
                [
                    'data' => [
                        'title'       => 'CI/CD Pipeline Template',
                        'description' => 'Plantilla reutilizable para pipelines de integración y despliegue continuo con GitHub Actions, Docker y despliegue automático a múltiples entornos.',
                        'project_url' => 'https://github.com/anagarcia-dev/cicd-template',
                        'category_id' => $cat('DevOps'),
                    ],
                    'skills' => $skill(['Docker', 'GitHub Actions', 'Nginx', 'Linux', 'CI/CD']),
                ],
            ];

            foreach ($projects as $p) {
                $project = $ana->portfolio->projects()->firstOrCreate(
                    ['title' => $p['data']['title']],
                    $p['data']
                );
                if (! empty($p['skills'])) {
                    $project->skills()->syncWithoutDetaching($p['skills']);
                }
            }
        }

        // ─────────────────────────────────────────────────
        // Carlos Méndez — UX/UI Designer
        // ─────────────────────────────────────────────────
        $carlos = User::where('email', 'carlos.mendez@portfolio.test')->first();
        if ($carlos && $carlos->portfolio) {
            $projects = [
                [
                    'data' => [
                        'title'       => 'Design System — SaaS Dashboard',
                        'description' => 'Sistema de diseño completo para un producto SaaS B2B. Incluye biblioteca de componentes, guía de estilos, tokens de diseño y documentación de uso.',
                        'project_url' => null,
                        'category_id' => $cat('UI/UX Design'),
                    ],
                    'skills' => $skill(['Figma']),
                ],
                [
                    'data' => [
                        'title'       => 'Mobile Banking App Redesign',
                        'description' => 'Rediseño completo de la experiencia de usuario de una app bancaria móvil. Foco en accesibilidad (WCAG 2.1 AA), flujos simplificados y reducción de fricción en pagos.',
                        'project_url' => null,
                        'category_id' => $cat('Mobile Development'),
                    ],
                    'skills' => $skill(['Figma', 'Jira']),
                ],
                [
                    'data' => [
                        'title'       => 'Accessibility Audit Toolkit',
                        'description' => 'Conjunto de herramientas y checklists para auditorías de accesibilidad web. Incluye guía de revisión manual, scripts de testing automatizado y plantillas de reporte.',
                        'project_url' => 'https://github.com/carlos-mendez-ux/a11y-toolkit',
                        'category_id' => $cat('Open Source'),
                    ],
                    'skills' => $skill(['Figma', 'GitHub']),
                ],
            ];

            foreach ($projects as $p) {
                $project = $carlos->portfolio->projects()->firstOrCreate(
                    ['title' => $p['data']['title']],
                    $p['data']
                );
                if (! empty($p['skills'])) {
                    $project->skills()->syncWithoutDetaching($p['skills']);
                }
            }
        }

        // ─────────────────────────────────────────────────
        // Sofía Romero — Data Scientist
        // ─────────────────────────────────────────────────
        $sofia = User::where('email', 'sofia.romero@portfolio.test')->first();
        if ($sofia && $sofia->portfolio) {
            $projects = [
                [
                    'data' => [
                        'title'       => 'Predictive Analytics Dashboard',
                        'description' => 'Dashboard interactivo de análisis predictivo para retail. Modelos de forecasting de ventas, detección de anomalías y segmentación de clientes. Reduce errores de inventario en un 23%.',
                        'project_url' => 'https://github.com/sofiaromero-ds/predictive-dashboard',
                        'category_id' => $cat('Data Science'),
                    ],
                    'skills' => $skill(['Python', 'R', 'PostgreSQL', 'Docker']),
                ],
                [
                    'data' => [
                        'title'       => 'NLP Sentiment Analyzer API',
                        'description' => 'API REST para análisis de sentimiento en textos en español. Entrenada con reseñas de productos latinoamericanos. Latencia < 120ms por request con batch processing.',
                        'project_url' => 'https://github.com/sofiaromero-ds/sentiment-api',
                        'category_id' => $cat('Machine Learning'),
                    ],
                    'skills' => $skill(['Python', 'FastAPI', 'PostgreSQL', 'Docker']),
                ],
                [
                    'data' => [
                        'title'       => 'Open Data Market Analyzer',
                        'description' => 'Herramienta de análisis de datos de mercado financiero usando fuentes abiertas. Incluye pipelines de ingesta, limpieza y visualización de series temporales.',
                        'project_url' => 'https://github.com/sofiaromero-ds/market-analyzer',
                        'category_id' => $cat('Open Source'),
                    ],
                    'skills' => $skill(['Python', 'PostgreSQL', 'ElasticSearch', 'GitHub']),
                ],
            ];

            foreach ($projects as $p) {
                $project = $sofia->portfolio->projects()->firstOrCreate(
                    ['title' => $p['data']['title']],
                    $p['data']
                );
                if (! empty($p['skills'])) {
                    $project->skills()->syncWithoutDetaching($p['skills']);
                }
            }
        }
    }
}
