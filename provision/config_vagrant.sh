#!/bin/bash
# As user vagrant:
ROOTDIR="/vagrant"

# Install nvm
curl -o- https://raw.githubusercontent.com/creationix/nvm/v0.33.6/install.sh | bash
export NVM_DIR="/home/vagrant/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"  # This loads nvm

# Install node
nvm install v6.12.0

# Install bower
npm install -g bower

# Install gulp
npm install -g gulp

# Install composer
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('SHA384', 'composer-setup.php') === '544e09ee996cdf60ece3804abc52599c22b1f40f4323403c44d44fdfdd586475ca9813a858088ffbc1f233e9b180f061') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
php composer-setup.php
php -r "unlink('composer-setup.php');"
if [ ! -d ~/bin ]; then
  mkdir ~/bin
fi
mv composer.phar ~/bin/composer


# Prepare running environment
if [ ! -f "${ROOTDIR}/src/server/.env" ]; then
  cp "${ROOTDIR}/src/server/.env.example" "${ROOTDIR}/src/server/.env"
fi

# Install dependencies
cd "${ROOTDIR}"
npm install
while [ $? != 0 ]; do
  npm install --no-bin-links 
done

cd src
bower install
# Bower sometimes fails when installing everything
while [ $? != 0 ]; do
  bower install
done

cd server
npm install
while [ $? != 0 ]; do
  npm install --no-bin-links 
done

php ~/bin/composer install
while [ $? != 0 ]; do
  php -d memory_limit=-1  ~/bin/composer install
done

# Compile assets into public folder
gulp bowercopy

php artisan migrate
php artisan db:seed
php artisan geoip:update

chmod -R a+wr /vagrant/src/server/{storage,bootstrap/cache}
