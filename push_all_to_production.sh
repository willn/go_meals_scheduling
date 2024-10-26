
rsync -e 'ssh' -avz --exclude '*.swp' public/ gocoho@gocoho.org:/home/gocoho/public_html/meals_scheduling/

#reset permissions
ssh -i ~/.ssh/id_dsa gocoho@gocoho.org 'cd ~/public_html/meals_scheduling/ && ~/bin/fix_web_perms.sh';


