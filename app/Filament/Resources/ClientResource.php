<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Client;
use App\Models\Address;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\CheckboxList;
use App\Filament\Resources\ClientResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ClientResource\RelationManagers;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        $client = new \GuzzleHttp\Client();

        return $form
            ->schema([
                Fieldset::make('Informações da empresa')
                    ->schema([
                        TextInput::make('cnpj')
                            ->label('CNPJ')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('00.000.000/0000-00')
                            ->mask('99.999.999/9999-99')
                            ->live()
                            ->afterStateUpdated(function (?string $state, Set $set) use ($client) {

                                session()->forget('cnpjFetched');

                                $cnpj = preg_replace('/[^0-9]/', '', $state);

                                if (strlen($cnpj) != 14) {
                                    $set('name', null);
                                    return;
                                }

                                $url = 'https://receitaws.com.br/v1/cnpj/' . $cnpj;

                                try {
                                    $response = $client->request('GET', $url, [
                                        'headers' => [
                                            'Accept' => 'application/json',
                                        ],
                                    ]);

                                    $data = json_decode($response->getBody());

                                    if ($data->status == 'ERROR') {
                                        Notification::make()
                                            ->title("CNPJ não encontrado!")
                                            ->danger()
                                            ->send();
                                        return;
                                    }

                                    $set('name', $data->nome);
                                    $set('fantasy_name', $data->fantasia);
                                    $set('email', $data->email);
                                    $set('phone', $data->telefone);
                                    $set('addresses', [
                                        [
                                            'district' => $data->bairro,
                                            'street' => $data->logradouro,
                                            'zip_code' => $data->cep,
                                            'city' => $data->municipio,
                                            'state' => $data->uf,
                                            'number' => $data->numero,
                                            'complement' => $data->complemento,
                                            'type' => 1,
                                        ],
                                    ]);
                                    $set('billingAddress', [
                                        'district' => $data->bairro,
                                        'street' => $data->logradouro,
                                        'zip_code' => $data->cep,
                                        'city' => $data->municipio,
                                        'state' => $data->uf,
                                        'number' => $data->numero,
                                        'complement' => $data->complemento,
                                    ]);

                                    $set('billing_email', $data->email);

                                    $originalCnpjData = [
                                        'name' => $data->nome,
                                        'fantasy_name' => $data->fantasia,
                                        'email' => $data->email,
                                        'phone' => $data->telefone,
                                        'addresses' => [
                                            [
                                                'district' => $data->bairro,
                                                'street' => $data->logradouro,
                                                'zip_code' => $data->cep,
                                                'city' => $data->municipio,
                                                'state' => $data->uf,
                                                'number' => $data->numero,
                                                'complement' => $data->complemento,
                                                'type' => 1,
                                            ],
                                        ],
                                        'billing_email' => $data->email,
                                    ];

                                    if (count($data->qsa) > 0) {
                                        $set('billing_responsible', $data->qsa[0]->nome);
                                        $originalCnpjData['billing_responsible'] = $data->qsa[0]->nome;
                                    }

                                    session()->put('cnpjFetched', true);

                                    session()->forget('originalCnpjData');
                                    session()->put('originalCnpjData', $originalCnpjData);
                                } catch (\GuzzleHttp\Exception\ClientException $e) {
                                    if ($e->getCode() == 429) {
                                        Notification::make()
                                            ->title("Muitas requisições!")
                                            ->warning()
                                            ->body("Por favor, tente novamente mais tarde.")
                                            ->send();
                                    } else {
                                        Notification::make()
                                            ->title("Erro na requisição!")
                                            ->danger()
                                            ->body("Ocorreu um erro ao tentar obter os dados do CNPJ.")
                                            ->send();
                                    }
                                } catch (\Exception $e) {
                                    Notification::make()
                                        ->title("Erro na requisição!")
                                        ->danger()
                                        ->body("Ocorreu um erro ao tentar obter os dados do CNPJ.")
                                        ->send();
                                }
                            }),
                        TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->placeholder('Nome da empresa')
                            ->maxLength(255)
                            ->live()
                            ->afterStateUpdated(function (?string $state) {
                                if (session()->get('cnpjFetched')) {
                                    if ($state != session()->get('originalCnpjData')['name']) {
                                        Notification::make()
                                            ->title("Dados oficiais alterados")
                                            ->warning()
                                            ->send();
                                    }
                                }
                            }),
                        TextInput::make('fantasy_name')
                            ->label('Nome Fantasia')
                            ->placeholder('Nome fantasia da empresa')
                            ->maxLength(255)
                            ->live()
                            ->afterStateUpdated(function (?string $state) {
                                if (session()->get('cnpjFetched')) {
                                    if ($state != session()->get('originalCnpjData')['fantasy_name']) {
                                        Notification::make()
                                            ->title("Dados oficiais alterados")
                                            ->warning()
                                            ->send();
                                    }
                                }
                            }),
                        TextInput::make('email')
                            ->email()
                            ->label('E-mail')
                            ->required()
                            ->placeholder('Email da empresa')
                            ->maxLength(255)
                            ->live()
                            ->afterStateUpdated(function (?string $state) {
                                if (session()->get('cnpjFetched')) {
                                    if ($state != session()->get('originalCnpjData')['email']) {
                                        Notification::make()
                                            ->title("Dados oficiais alterados")
                                            ->warning()
                                            ->send();
                                    }
                                }
                            }),
                        TextInput::make('phone')
                            ->label('Telefone')
                            ->required()
                            ->mask('(99) 9999-9999')
                            ->placeholder('(00) 0000-0000')
                            ->live()
                            ->afterStateUpdated(function (?string $state) {
                                if (session()->get('cnpjFetched')) {
                                    if ($state != session()->get('originalCnpjData')['phone']) {
                                        Notification::make()
                                            ->title("Dados oficiais alterados")
                                            ->warning()
                                            ->send();
                                    }
                                }
                            }),
                        FileUpload::make('logo')
                            ->label('Logo')
                            ->image()
                            ->avatar(),
                        Select::make('employee_quantity')
                            ->label('Quantidade de funcionários')
                            ->required()
                            ->options([
                                1 => '0-10',
                                2 => '11-50',
                                3 => '51-150',
                                4 => '151-300',
                                5 => '300+',
                            ]),
                        Select::make('company_size')
                            ->label('Porte da empresa')
                            ->required()
                            ->options([
                                1 => 'Micro',
                                2 => 'Pequeno',
                                3 => 'Médio',
                                4 => 'Grande',
                            ]),
                        Select::make('industry_segment')
                            ->label('Segmento da empresa')
                            ->required()
                            ->options([
                                1 => 'Indústria',
                                2 => 'Comércio',
                                3 => 'Serviço',
                            ]),
                        Select::make('structured_hr_department')
                            ->label('Departamento de RH estruturado')
                            ->required()
                            ->options([
                                1 => 'Sim',
                                0 => 'Não',
                            ]),
                        Textarea::make('company_profile')
                            ->label('Perfil da empresa')
                            ->required()
                            ->placeholder('Perfil da empresa')
                            ->autosize()
                            ->rows(3)
                            ->cols(3)
                            ->maxLength(1000),
                        Repeater::make('addresses')
                            ->label('Endereços')
                            ->schema([
                                TextInput::make('district')
                                    ->label('Bairro')
                                    ->name('district')
                                    ->required()
                                    ->maxLength(255)
                                    ->live()
                                    ->afterStateUpdated(function (?string $state, Component $component) {
                                        if (session()->get('cnpjFetched')) {
                                            if ($component->getStatePath() === 'data.addresses.record-1.district' && $state != session()->get('originalCnpjData')['addresses'][0]['district']) {
                                                Notification::make()
                                                    ->title("Dados oficiais alterados")
                                                    ->warning()
                                                    ->send();
                                            }
                                        }
                                    }),
                                TextInput::make('street')
                                    ->label('Rua')
                                    ->required()
                                    ->maxLength(255)
                                    ->live()
                                    ->afterStateUpdated(function (?string $state, Component $component) {
                                        if (session()->get('cnpjFetched')) {
                                            if ($component->getStatePath() === 'data.addresses.record-1.street' && $state != session()->get('originalCnpjData')['addresses'][0]['street']) {
                                                Notification::make()
                                                    ->title("Dados oficiais alterados")
                                                    ->warning()
                                                    ->send();
                                            }
                                        }
                                    }),
                                TextInput::make('zip_code')
                                    ->label('CEP')
                                    ->required()
                                    ->placeholder('00.000-000')
                                    ->mask('99.999-999')
                                    ->live()
                                    ->afterStateUpdated(function (?string $state, Component $component) {
                                        if (session()->get('cnpjFetched')) {
                                            if ($component->getStatePath() === 'data.addresses.record-1.zip_code' && $state != session()->get('originalCnpjData')['addresses'][0]['zip_code']) {
                                                Notification::make()
                                                    ->title("Dados oficiais alterados")
                                                    ->warning()
                                                    ->send();
                                            }
                                        }
                                    }),
                                TextInput::make('city')
                                    ->label('Cidade')
                                    ->required()
                                    ->maxLength(255)
                                    ->live()
                                    ->afterStateUpdated(function (?string $state, Component $component) {
                                        if (session()->get('cnpjFetched')) {
                                            if ($component->getStatePath() === 'data.addresses.record-1.city' && $state != session()->get('originalCnpjData')['addresses'][0]['city']) {
                                                Notification::make()
                                                    ->title("Dados oficiais alterados")
                                                    ->warning()
                                                    ->send();
                                            }
                                        }
                                    }),
                                TextInput::make('state')
                                    ->label('Estado')
                                    ->required()
                                    ->maxLength(255)
                                    ->live()
                                    ->afterStateUpdated(function (?string $state, Component $component) {
                                        if (session()->get('cnpjFetched')) {
                                            if ($component->getStatePath() === 'data.addresses.record-1.state' && $state != session()->get('originalCnpjData')['addresses'][0]['state']) {
                                                Notification::make()
                                                    ->title("Dados oficiais alterados")
                                                    ->warning()
                                                    ->send();
                                            }
                                        }
                                    }),
                                TextInput::make('number')
                                    ->label('Número')
                                    ->required()
                                    ->maxLength(255)
                                    ->live()
                                    ->afterStateUpdated(function (?string $state, Component $component) {
                                        if (session()->get('cnpjFetched')) {
                                            if ($component->getStatePath() === 'data.addresses.record-1.number' && $state != session()->get('originalCnpjData')['addresses'][0]['number']) {
                                                Notification::make()
                                                    ->title("Dados oficiais alterados")
                                                    ->warning()
                                                    ->send();
                                            }
                                        }
                                    }),
                                TextInput::make('complement')
                                    ->label('Complemento')
                                    ->maxLength(255)
                                    ->live()
                                    ->afterStateUpdated(function (?string $state, Component $component) {
                                        if (session()->get('cnpjFetched')) {
                                            if ($component->getStatePath() === 'data.addresses.record-1.complement' && $state != session()->get('originalCnpjData')['addresses'][0]['complement']) {
                                                Notification::make()
                                                    ->title("Dados oficiais alterados")
                                                    ->warning()
                                                    ->send();
                                            }
                                        }
                                    }),
                                Select::make('type')
                                    ->label('Tipo de Endereço')
                                    ->required()
                                    ->options([
                                        1 => 'Sede',
                                        2 => 'Unidade',
                                    ]),
                            ])
                            ->relationship('addresses')
                            ->columns(2)
                            ->columnSpanFull()
                            ->addActionLabel('Adicionar endereço')
                            ->itemLabel(function (array $state) {
                                return $state['street'] ?? null;
                            }),
                    ])
                    ->columns(1),
                Fieldset::make('Informações do responsável')
                    ->schema([
                        TextInput::make('responsible_name')
                            ->label('Nome do responsável')
                            ->placeholder('Nome do responsável pela empresa')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('responsible_email')
                            ->email()
                            ->label('E-mail do responsável')
                            ->placeholder('E-mail do responsável pela empresa')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('responsible_phone')
                            ->label('Telefone do responsável')
                            ->placeholder('(00) 0000-0000')
                            ->mask('(99) 9999-9999')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('responsible_whatsapp')
                            ->label('WhatsApp do responsável')
                            ->placeholder('WhatsApp do responsável pela empresa')
                            ->required()
                            ->mask('(99) 9999-9999')
                            ->maxLength(255),
                    ])
                    ->columns(1),
                Fieldset::make('Detalhes sobre a empresa')
                    ->schema([
                        Textarea::make('mission')
                            ->label('Missão')
                            ->placeholder('Missão da empresa')
                            ->autosize()
                            ->rows(3)
                            ->cols(3)
                            ->maxLength(1000),
                        Select::make('pdi_program')
                            ->label('Programa de PDI')
                            ->options([
                                1 => 'Sim',
                                0 => 'Não',
                            ]),
                        Textarea::make('values')
                            ->label('Valores')
                            ->placeholder('Valores da empresa')
                            ->autosize()
                            ->rows(3)
                            ->cols(3)
                            ->maxLength(1000),
                        CheckboxList::make('work_regimes')
                            ->label('Regime de trabalho')
                            ->options([
                                1 => 'CLT',
                                2 => 'Autônomo',
                                3 => 'Trainee',
                                4 => 'Estagiário',
                                5 => 'Jovem Aprendiz',
                                6 => 'Menor Aprendiz',
                            ]),
                    ])
                    ->columns(1),
                Fieldset::make('Informações de cobrança')
                    ->schema([
                        Select::make('billing_address_id')
                            ->label('Alterar endereço de cobrança')
                            ->hidden(fn (string $operation): bool => $operation === 'create')
                            ->options(function (callable $get) {
                                $clientId = $get('id');
                                if ($clientId) {
                                    return Address::where('client_id', $clientId)
                                        ->pluck('street', 'id')->toArray();
                                }
                                return [];
                            })
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $address = Address::find($state);
                                if ($address) {
                                    $set('billingAddress', [
                                        'district' => $address->district,
                                        'street' => $address->street,
                                        'number' => $address->number,
                                        'zip_code' => $address->zip_code,
                                        'complement' => $address->complement,
                                        'city' => $address->city,
                                        'state' => $address->state,
                                    ]);
                                }
                            }),
                        Section::make('Endereço de cobrança')
                            ->relationship('billingAddress')
                            ->schema([
                                TextInput::make('district')
                                    ->label('Bairro')
                                    ->name('district')
                                    ->required()
                                    ->maxLength(255)
                                    ->live()
                                    ->afterStateUpdated(function (?string $state) {
                                        if (session()->get('cnpjFetched')) {
                                            if ($state != session()->get('originalCnpjData')['district']) {
                                                Notification::make()
                                                    ->title("Dados oficiais alterados")
                                                    ->warning()
                                                    ->send();
                                            }
                                        }
                                    }),
                                TextInput::make('street')
                                    ->label('Rua')
                                    ->required()
                                    ->maxLength(255)
                                    ->live()
                                    ->afterStateUpdated(function (?string $state) {
                                        if (session()->get('cnpjFetched')) {
                                            if ($state != session()->get('originalCnpjData')['street']) {
                                                Notification::make()
                                                    ->title("Dados oficiais alterados")
                                                    ->warning()
                                                    ->send();
                                            }
                                        }
                                    }),
                                TextInput::make('zip_code')
                                    ->label('CEP')
                                    ->required()
                                    ->placeholder('00.000-000')
                                    ->mask('99.999-999')
                                    ->live()
                                    ->afterStateUpdated(function (?string $state) {
                                        if (session()->get('cnpjFetched')) {
                                            if ($state != session()->get('originalCnpjData')['zip_code']) {
                                                Notification::make()
                                                    ->title("Dados oficiais alterados")
                                                    ->warning()
                                                    ->send();
                                            }
                                        }
                                    }),
                                TextInput::make('city')
                                    ->label('Cidade')
                                    ->required()
                                    ->maxLength(255)
                                    ->live()
                                    ->afterStateUpdated(function (?string $state) {
                                        if (session()->get('cnpjFetched')) {
                                            if ($state != session()->get('originalCnpjData')['ity']) {
                                                Notification::make()
                                                    ->title("Dados oficiais alterados")
                                                    ->warning()
                                                    ->send();
                                            }
                                        }
                                    }),
                                TextInput::make('state')
                                    ->label('Estado')
                                    ->required()
                                    ->maxLength(255)
                                    ->live()
                                    ->afterStateUpdated(function (?string $state) {
                                        if (session()->get('cnpjFetched')) {
                                            if ($state != session()->get('originalCnpjData')['state']) {
                                                Notification::make()
                                                    ->title("Dados oficiais alterados")
                                                    ->warning()
                                                    ->send();
                                            }
                                        }
                                    }),
                                TextInput::make('number')
                                    ->label('Número')
                                    ->required()
                                    ->maxLength(255)
                                    ->live()
                                    ->afterStateUpdated(function (?string $state) {
                                        if (session()->get('cnpjFetched')) {
                                            if ($state != session()->get('originalCnpjData')['number']) {
                                                Notification::make()
                                                    ->title("Dados oficiais alterados")
                                                    ->warning()
                                                    ->send();
                                            }
                                        }
                                    }),
                                TextInput::make('complement')
                                    ->label('Complemento')
                                    ->maxLength(255)
                                    ->live()
                                    ->afterStateUpdated(function (?string $state) {
                                        if (session()->get('cnpjFetched')) {
                                            if ($state != session()->get('originalCnpjData')['complement']) {
                                                Notification::make()
                                                    ->title("Dados oficiais alterados")
                                                    ->warning()
                                                    ->send();
                                            }
                                        }
                                    }),
                            ])
                            ->columns(2),
                        TextInput::make('billing_email')
                            ->email()
                            ->label('E-mail de cobrança')
                            ->required()
                            ->placeholder('E-mail de cobrança')
                            ->maxLength(255)
                            ->live()
                            ->afterStateUpdated(function (?string $state) {
                                if (session()->get('cnpjFetched')) {
                                    if ($state != session()->get('originalCnpjData')['billing_email']) {
                                        Notification::make()
                                            ->title("Dados oficiais alterados")
                                            ->warning()
                                            ->send();
                                    }
                                }
                            }),
                        TextInput::make('billing_responsible')
                            ->label('Responsável pela cobrança')
                            ->required()
                            ->placeholder('Responsável pela cobrança')
                            ->maxLength(255)
                            ->live()
                            ->afterStateUpdated(function (?string $state) {
                                if (session()->get('cnpjFetched')) {
                                    if ($state != session()->get('originalCnpjData')['billing_responsible']) {
                                        Notification::make()
                                            ->title("Dados oficiais alterados")
                                            ->warning()
                                            ->send();
                                    }
                                }
                            }),
                        Select::make('payment_methods')
                            ->label('Forma de pagamento')
                            ->required()
                            ->options([
                                1 => 'Boleto',
                                2 => 'Contrato Faturado',
                                3 => 'Pix',
                                4 => 'Cartão',
                            ]),
                        DatePicker::make('payment_date')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->label('Data de pagamento')
                            ->placeholder('Data de pagamento')
                            ->required(),
                        Select::make('contract_type')
                            ->label('Tipo de contrato')
                            ->required()
                            ->options([
                                1 => 'Recorrente',
                                2 => 'Por uso',
                            ])
                            ->live(),
                        Select::make('contract_package')
                            ->label('Pacote')
                            ->requiredIf('contract_type', 1)
                            ->options([
                                1 => 'Pacote A',
                                2 => 'Pacote B',
                                3 => 'Pacote C',
                            ])
                            ->visible(fn (Get $get): bool => $get('contract_type') == 1),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('cnpj')
                    ->label('CNPJ')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('fantasy_name')
                    ->label('Nome Fantasia')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->label('Telefone')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }
}
