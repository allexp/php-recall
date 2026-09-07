FROM php:8.3-cli-alpine

RUN apk add --no-cache docker-cli

WORKDIR /runner
COPY router.php /runner/router.php

USER nobody
EXPOSE 8080

CMD ["php", "-S", "0.0.0.0:8080", "/runner/router.php"]
