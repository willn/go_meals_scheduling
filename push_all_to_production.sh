
rsync -e 'ssh -p 1022' -avz public/ gocoho@gocoho.org:/home/gocoho/public_html/meals_scheduling/

#reset permissions
ssh -i ~/.ssh/id_dsa -p 1022 gocoho@gocoho.org 'chmod -R g-w ~/public_html/meals_scheduling/'

