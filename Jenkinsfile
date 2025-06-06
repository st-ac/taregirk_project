pipeline {
    
    agent none
    
    stages {
        stage('git repo') {
            agent any
            steps {
                git branch: 'main', url: 'https://github.com/st-ac/taregirk_project' 
            }
        }
        stage('sonarqube') {
            agent { label 'agent-php' }
            steps {
                sh """
            sonar-scanner \
  -Dsonar.projectKey=Steph_TG_Back \
  -Dsonar.sources=. \
  -Dsonar.host.url=https://2a26-212-114-26-208.ngrok-free.app \
  -Dsonar.token=sqp_c20c38c1b19115b875c77332b97bfd9b84c54236
            """
            }
        }
    }
}
