<?php

namespace PHPSTORM_META {
    // Filament Forms Components
    override(\Filament\Forms\Components\Component::label(), map([
        '' => '@|static',
    ]));

    override(\Filament\Forms\Components\Component::default(), map([
        '' => '@|static',
    ]));

    override(\Filament\Forms\Components\Component::columnSpanFull(), map([
        '' => '@|static',
    ]));

    override(\Filament\Forms\Components\Component::required(), map([
        '' => '@|static',
    ]));

    override(\Filament\Forms\Components\Component::numeric(), map([
        '' => '@|static',
    ]));

    override(\Filament\Forms\Components\Component::maxLength(), map([
        '' => '@|static',
    ]));

    override(\Filament\Forms\Components\Component::options(), map([
        '' => '@|static',
    ]));

    override(\Filament\Forms\Components\Component::relationship(), map([
        '' => '@|static',
    ]));

    // Filament Tables Components
    override(\Filament\Tables\Columns\Column::label(), map([
        '' => '@|static',
    ]));

    override(\Filament\Tables\Columns\Column::sortable(), map([
        '' => '@|static',
    ]));

    override(\Filament\Tables\Columns\Column::searchable(), map([
        '' => '@|static',
    ]));
}


