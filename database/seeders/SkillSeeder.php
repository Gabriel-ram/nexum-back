<?php

namespace Database\Seeders;

use App\Models\Skill;
use Illuminate\Database\Seeder;

class SkillSeeder extends Seeder
{
    public function run(): void
    {
        // --- Skills técnicas del portfolio (type = 'tecnica') ---
        $technical = [
            'Lenguajes de Programación' => [
                'Python', 'JavaScript', 'TypeScript', 'Java', 'C#', 'C++', 'C', 'Go',
                'Rust', 'PHP', 'Ruby', 'Swift', 'Kotlin', 'Dart', 'Scala', 'R', 'MATLAB',
            ],
            'Frameworks & Librerías' => [
                'React', 'Angular', 'Vue.js', 'Next.js', 'Svelte', 'Node.js', 'Express.js',
                'NestJS', 'Django', 'FastAPI', 'Flask', 'Spring Boot', 'Laravel',
                'Ruby on Rails', 'Flutter', 'React Native', '.NET', 'ASP.NET',
            ],
            'Bases de Datos' => [
                'MySQL', 'PostgreSQL', 'SQLite', 'SQL Server', 'Oracle DB', 'MongoDB',
                'Firebase', 'Redis', 'Cassandra', 'DynamoDB', 'Supabase', 'MariaDB', 'ElasticSearch',
            ],
            'Cloud & DevOps' => [
                'AWS', 'Azure', 'Google Cloud (GCP)', 'Docker', 'Kubernetes', 'GitHub Actions',
                'Jenkins', 'Terraform', 'Ansible', 'Linux', 'Nginx', 'CI/CD',
                'Vercel', 'Railway', 'DigitalOcean',
            ],
            'Herramientas & Plataformas' => [
                'Git', 'GitHub', 'GitLab', 'Bitbucket', 'Jira', 'Trello', 'Figma',
                'Postman', 'VS Code', 'IntelliJ IDEA', 'Eclipse', 'Webpack', 'Vite',
                'npm', 'Yarn', 'Swagger',
            ],
        ];

        // --- Skills blandas del portfolio (type = 'blanda') ---
        $soft = [
            'Comunicación' => [
                'Comunicación técnica', 'Documentación', 'Presentación de ideas',
                'Feedback constructivo', 'Redacción técnica', 'Comunicación asertiva',
            ],
            'Colaboración' => [
                'Trabajo en equipo', 'Colaboración remota', 'Mentoría',
                'Pair programming', 'Revisión de código', 'Apoyo entre compañeros',
            ],
            'Liderazgo & Gestión' => [
                'Liderazgo técnico', 'Toma de decisiones', 'Gestión de prioridades',
                'Delegación', 'Motivación de equipos', 'Visión estratégica',
            ],
            'Pensamiento Analítico' => [
                'Resolución de problemas', 'Pensamiento crítico', 'Atención al detalle',
                'Pensamiento analítico', 'Investigación', 'Innovación',
            ],
            'Desarrollo Personal' => [
                'Aprendizaje continuo', 'Adaptabilidad', 'Autonomía',
                'Gestión del tiempo', 'Proactividad', 'Autodisciplina', 'Tolerancia a la presión',
            ],
        ];

        // --- Categorías de proyecto (type = 'project_category') ---
        // Reemplaza la tabla 'categories' eliminada. El usuario selecciona
        // a qué área pertenece su proyecto al igual que cualquier otra skill.
        $projectCategories = [
            'Web Development',
            'Mobile Development',
            'Data Science',
            'DevOps',
            'UI/UX Design',
            'Machine Learning',
            'Cybersecurity',
            'Open Source',
            'Other',
        ];

        foreach ($technical as $category => $names) {
            foreach ($names as $name) {
                Skill::firstOrCreate(
                    ['name' => $name, 'type' => 'tecnica'],
                    ['category' => $category]
                );
            }
        }

        foreach ($soft as $category => $names) {
            foreach ($names as $name) {
                Skill::firstOrCreate(
                    ['name' => $name, 'type' => 'blanda'],
                    ['category' => $category]
                );
            }
        }

        foreach ($projectCategories as $name) {
            Skill::firstOrCreate(
                ['name' => $name, 'type' => 'project_category'],
                ['category' => 'Categoría de Proyecto']
            );
        }
    }
}
