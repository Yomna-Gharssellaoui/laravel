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

        stage('ML models') {
            steps {
                dir('ml') {
                    sh 'python3 -m venv .venv && .venv/bin/pip install -q -r requirements.txt'
                    sh '.venv/bin/python train_all.py && .venv/bin/python -m pytest -q --junitxml=../storage/logs/ml-junit.xml'
                }
            }
            post {
                always {
                    junit allowEmptyResults: true, testResults: 'storage/logs/ml-junit.xml'
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
                sh "docker build -t ${IMAGE}-ml:${TAG} -t ${IMAGE}-ml:latest ml"
            }
        }
    }

    post {
        always {
            sh 'rm -f .env'
        }
    }
}
