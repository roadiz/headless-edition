printenv | sed 's/^\(.*\)$/export \1/g' | grep -E "^export (SYMFONY|APP|ROADIZ|MYSQL|JWT|MAILER|SOLR|VARNISH)" > /var/www/html/project_env.sh

# Fix volume permissions
/bin/chown -R www-data:www-data /var/www/html/files;
/bin/chown -R www-data:www-data /var/www/html/web;
/bin/chown -R www-data:www-data /var/www/html/app;

/bin/chmod +x /var/www/html/project_env.sh;

# Wait for database to be ready.
/bin/sleep 5s;

# Uncomment following line to enable automatic migration for your theme at each docker start
/usr/bin/sudo -u www-data -- bash -c "/var/www/html/project_env.sh; /var/www/html/bin/roadiz migrations:migrate -q --allow-no-migration"
/usr/bin/sudo -u www-data -- bash -c "/var/www/html/project_env.sh; /var/www/html/bin/roadiz themes:migrate -n src/Resources/config.yml"
