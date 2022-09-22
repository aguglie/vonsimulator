FROM php:5.6.40-zts-alpine
WORKDIR /usr/app

COPY . ./

CMD ["/usr/app/start.sh"]
