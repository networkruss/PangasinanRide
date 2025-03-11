# README

## Frontend Setup


1. On terminal, type:
   `cd frontend && npm i`
2. After that, you can run it by typing:
   `npm run build`
3. Then
   `npm run preview`

## Backend Setup

1. On terminal, type:
   `php spark db:create pr_db`
2. On terminal, after creating the database, type:
   `php spark migrate:refresh`
3. If migration completed, you can now type:
   `php spark serve`
   to run the backend server.