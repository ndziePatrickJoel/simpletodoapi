# install AWS SDK
pip install --user awscli
export PATH=$PATH:$HOME/.local/bin

# install necessary dependency for ecs-deploy
add-apt-repository ppa:eugenesan/ppa
apt-get update
apt-get install jq -y

# install ecs-deploy
curl https://raw.githubusercontent.com/silinternational/ecs-deploy/master/ecs-deploy | \
  sudo tee -a /usr/bin/ecs-deploy
sudo chmod +x /usr/bin/ecs-deploy

# login AWS ECR
# eval $(aws ecr get-login --region us-east-1)

# or login DockerHub
docker login --username $DOCKER_HUB_USER --password $DOCKER_HUB_PSW

# build the docker image and push to an image repository

docker run  --name test_container --env APP_ENV=prod --env DATABASE_URL=$DATABASE_URL --env APP_SECRET=d93eac76d89ca352fc00878672daf0c2   diceprime/todoapi:latest php /var/www/html/bin/phpunit

docker rmi -f diceprime/todoapi:latest

# update an AWS ECS service with the new image
ecs-deploy -c $CLUSTER_NAME -n $SERVICE_NAME -i diceprime/todoapi:latest