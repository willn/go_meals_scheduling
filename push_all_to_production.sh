
HOST='meals_sc1@65.181.116.46';

rsync -e 'ssh' -avz --exclude '*.swp' public/ $HOST:public_html/

#reset permissions
#ssh $HOST 'cd ~/public_html/meals_scheduling/ && ~/bin/fix_web_perms.sh';


