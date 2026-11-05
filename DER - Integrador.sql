create database if not exists embrapa_bd;
use embrapa_bd;

create table usuario(
	id INT auto_increment primary Key,
    nome VARCHAR(100) not null,
    email VARCHAR(100) not null unique,
    senha VARCHAR(50) not null,
    matricula INT not null,
    ativo boolean not null default 1
);

create table categoria(
	id INT auto_increment primary Key,
    nome VARCHAR(100) not null
);

create table acao(
	id INT auto_increment primary Key,
    titulo VARCHAR(100) not null,
    descricao TEXT not null,
    data_de_realizacao DATE not null,
    status_acao ENUM('Aprovado', 'Negado', 'Em Avaliação') NOT NULL DEFAULT 'Em Avaliação',
    categoria_id INT not null,
    foreign key (categoria_id) references categoria(id)
);

create table cargo(
	id INT auto_increment primary Key,
    titulo VARCHAR(100) not null
);

create table usuario_cargo(
    usuario_id INT not null,
    cargo_id INT not null,
	primary key(usuario_id, cargo_id),
    foreign key (usuario_id) references usuario(id),
    foreign key (cargo_id) references cargo(id)
);

create table acao_usuario(
	primary key(usuario_id, acao_id),
	usuario_id INT not null,
    acao_id INT not null,
    foreign key (usuario_id) references usuario(id),
    foreign key (acao_id) references acao(id)
);

create table validacao(
	primary key(usuario_id, acao_id),
	motivo_rejeicao TEXT,
    usuario_id INT not null,
    acao_id INT not null,
    foreign key (usuario_id) references usuario(id),
    foreign key (acao_id) references acao(id)
);

create table log_usuario(
	id INT auto_increment primary Key,
    operacao VARCHAR(100) not null,
    tabela_afetada VARCHAR(100) not null,
    id_registrado INT not null,
    data_hora datetime not null,
    matricula_id INT not null,
    foreign key (matricula_id) references usuario(id)
);

insert ignore into usuario(nome, email, senha, matricula)
values('Administrador', 'admin@admin.com', '12345', '00001');

INSERT IGNORE INTO cargo (titulo)
VALUES
('Pesquisador'),
('Validador'),
('Comitê'),
('Administrador');

insert ignore into usuario_cargo (usuario_id, cargo_id)
select u.id, c.id
from usuario u
cross join cargo c
where u.email = 'admin@admin.com'
and c.titulo = 'Administrador';

SELECT
    u.id,
    u.nome,
    u.email,
    u.senha,
    u.matricula,
    u.ativo,
    c.titulo AS cargo
FROM usuario u
INNER JOIN usuario_cargo uc ON u.id = uc.usuario_id
INNER JOIN cargo c ON uc.cargo_id = c.id;