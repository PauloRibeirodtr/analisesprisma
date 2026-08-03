<?php

namespace App;

enum StatusJanelaPagamentoDoubleBranco: string
{
    case Agendada = 'agendada';
    case Executando = 'executando';
    case Win = 'win';
    case Loss = 'loss';
    case CanceladaRecuperacao = 'cancelada_recuperacao';
}
