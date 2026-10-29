// Jenkins pipeline: install, quality, tests, SonarQube analysis, Docker image.
// Required on the agent: PHP 8.2+, Composer, Node 22, Docker.
// Optional: a SonarQube server declared in Jenkins as "sonarqube" + the "sonar-scanner" tool.
pipeline {
    agent any

    environment {
        IMAGE = "trace-verte"
        TAG = "${env.BUILD_NUMBER}"
    }

    stages {
        stage('Install') {
            steps {
                sh 'composer install --no-interaction --prefer-dist --no-progress'
                sh 'cp .env.example .env && php artisan key:generate'
                sh 'npm ci'
            }
        }

        stage('Code style') {
            steps {
                sh 'vendor/bin/pint --test'
            }
        }

        stage('Tests') {
            steps {
                sh 'php artisan test --log-junit storage/logs/junit.xml'
            }
            post {
                always {
                    junit allowEmptyResults: true, testResults: 'storage/logs/junit.xml'
                }
            }
        }

        stage('Front-end build') {
            steps {
                sh 'npm run build'
            }
        }

        stage('SonarQube') {
            when { expression { return env.SONAR_ENABLED == 'true' } }
            steps {
                withSonarQubeEnv('sonarqube') {
                    sh "${tool 'sonar-scanner'}/bin/sonar-scanner"
                }
            }
        }

        stage('Docker image') {
            steps {
                sh "docker build -t ${IMAGE}:${TAG} -t ${IMAGE}:latest ."
            }
        }
    }

    post {
        always {
            sh 'rm -f .env'
        }
    }
}
