-- -------------------------------------------------------
-- Project Name: --------- Role & Permission Management --
-- Database: ------------- PostgreSQL --------------------
-- Export Date: ---------- 2026-08-19 14:39 --------------
-- Created By: ----------- dbdesign.io -------------------
-- -------------------------------------------------------

-- ---------- TABLE MENU_GRUPO -----------
CREATE TABLE IF NOT EXISTS menu_grupo (
  id          int,
  grupo       varchar(255) NOT NULL,
  icono       varchar(255) NOT NULL,
  estado      bool NOT NULL DEFAULT 'true',
  creado_en   datetime NOT NULL DEFAULT 'now()',
  CONSTRAINT pk_menu_grupo PRIMARY KEY (id)
);

-- ---------- TABLE MENU -----------
CREATE TABLE IF NOT EXISTS menu (
  id              int,
  menu_grupo_id   int NOT NULL,
  menu            varchar(255) NOT NULL,
  menu_padre_id   int,
  icono           varchar(255),
  estado          bool NOT NULL DEFAULT 'true',
  creado_en       datetime NOT NULL DEFAULT 'now()',
  
  CONSTRAINT pk_menu PRIMARY KEY (id)
);

-- ---------- TABLE ROLE_PERMISO -----------
CREATE TABLE IF NOT EXISTS role_permiso (
  id              int,
  role            varchar(255) NOT NULL,
  role_padre_id   int,
  estado          bool NOT NULL DEFAULT 'true',
  creado_en       datetime NOT NULL DEFAULT 'now()',
  
  CONSTRAINT pk_role_permiso PRIMARY KEY (id)
);

-- ---------- TABLE USUARIO -----------
CREATE TABLE IF NOT EXISTS usuario (
  id                       int,
  usuario                  varchar(255) NOT NULL,
  email                    varchar(255) NOT NULL,
  password                 varchar(255),
  nombre                   varchar(255),
  apellido                 varchar(255),
  telefono                 varchar(255),
  documento                varchar(255),
  token_web                int,
  politica_datos           bool NOT NULL,
  politica_datos_en        datetime,
  fallos                   int,
  estado                   bool NOT NULL DEFAULT 'true',
  creado_en                datetime NOT NULL DEFAULT 'now()',
  modificado_en            datetime,
  password_modificada_en   datetime NOT NULL DEFAULT 'now()',
  
  CONSTRAINT pk_usuario PRIMARY KEY (id)
);

-- ---------- TABLE USUARIO_LOG -----------
CREATE TABLE IF NOT EXISTS usuario_log (
  id           int,
  usuario_id   int NOT NULL,
  ip           varchar(255),
  estado       varchar(255),
  creado_en    datetime NOT NULL DEFAULT 'now()',
  
  CONSTRAINT pk_usuario_log PRIMARY KEY (id)
);

-- ---------- TABLE USUARIO_ROLE_PERMISO -----------
CREATE TABLE IF NOT EXISTS usuario_role_permiso (
  id                int,
  role_permiso_id   int NOT NULL,
  usuario_id        int NOT NULL,
  estado            bool NOT NULL DEFAULT 'true',
  creado_en         datetime NOT NULL DEFAULT 'now()',
  
  CONSTRAINT pk_usuario_role_permiso PRIMARY KEY (id)
);

-- ---------- TABLE MENU_ROLE_PERMISO -----------
CREATE TABLE IF NOT EXISTS menu_role_permiso (
  id                int,
  role_permiso_id   int NOT NULL,
  menu_id           int NOT NULL,
  estado            bool NOT NULL DEFAULT 'true',
  creado_en         datetime NOT NULL DEFAULT 'now()',
  
  CONSTRAINT pk_menu_role_permiso PRIMARY KEY (id)
);

-- ---------- FOREIGN KEYS -----------
ALTER TABLE menu                 ADD CONSTRAINT fk_menu_menu_grupo_id FOREIGN KEY (menu_grupo_id) REFERENCES menu_grupo(id);
ALTER TABLE menu                 ADD CONSTRAINT fk_menu_menu_padre_id FOREIGN KEY (menu_padre_id) REFERENCES menu(id);
ALTER TABLE role_permiso         ADD CONSTRAINT fk_role_permiso_role_padre_id FOREIGN KEY (role_padre_id) REFERENCES role_permiso(id);
ALTER TABLE usuario_role_permiso ADD CONSTRAINT fk_usuario_role_permiso_role_permiso_id FOREIGN KEY (role_permiso_id) REFERENCES role_permiso(id);
ALTER TABLE usuario_role_permiso ADD CONSTRAINT fk_usuario_role_permiso_usuario_id FOREIGN KEY (usuario_id) REFERENCES usuario(id);
ALTER TABLE menu_role_permiso    ADD CONSTRAINT fk_menu_role_permiso_role_permiso_id FOREIGN KEY (role_permiso_id) REFERENCES role_permiso(id);
ALTER TABLE menu_role_permiso    ADD CONSTRAINT fk_menu_role_permiso_menu_id FOREIGN KEY (menu_id) REFERENCES menu(id);