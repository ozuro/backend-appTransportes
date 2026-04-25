# Modelo de operacion de flota

La base actual del sistema ya tenia empresa, choferes, vehiculos, servicios e ingresos.
Para soportar escenarios mas reales sin romper el flujo actual se agrego una capa nueva
de historial y liquidacion por servicio.

## Casos que cubre

- Un administrador con varios camiones y varios choferes.
- Un dueno que maneja su propio vehiculo y tambien administra otro con chofer.
- Una empresa mas grande con choferes de medio tiempo o relevo.

## Tablas nuevas

### driver_vehicle_assignments

Guarda el historial de asignaciones entre chofer y vehiculo.

Sirve para:

- tiempo completo
- medio tiempo
- relevo
- chofer temporal
- dueno que tambien opera una unidad

Campos clave:

- `assignment_type`
- `is_primary`
- `starts_at`
- `ends_at`
- `assigned_by_user_id`

### service_settlements

Guarda la liquidacion economica de cada servicio terminado.

Sirve para separar:

- lo que corresponde a la empresa
- lo que corresponde al propietario de la unidad
- lo que corresponde al chofer
- gastos asociados al servicio

Campos clave:

- `gross_amount`
- `company_amount`
- `owner_amount`
- `driver_amount`
- `expense_amount`
- `status`

## Cambios en tablas existentes

### vehicles

Se agrego:

- `owner_user_id`
- `management_mode`

Esto permite distinguir entre:

- flota de empresa
- unidad de propietario que opera dentro de la empresa
- unidad administrada por encargo

### cash_incomes

Se agrego trazabilidad para enlazar el ingreso con:

- servicio
- vehiculo
- chofer
- usuario que registro
- beneficiario real

## Regla operativa recomendada

1. `transport_services` sigue siendo la fuente principal de operacion.
2. `driver_vehicle_assignments` guarda quien puede usar una unidad y desde cuando.
3. `service_settlements` define como se reparte el dinero de un servicio.
4. `cash_incomes` registra la cobranza real con trazabilidad.

## Ejemplos

### Caso 1: administrador con dos camiones

- Empresa: Transportes X
- Vehiculos: Camion A, Camion B
- Choferes: Juan, Pedro

Cada servicio se registra con su `vehicle_id` y `driver_id`.
La liquidacion final se guarda en `service_settlements`.

### Caso 2: dueno que maneja un vehiculo y tiene otro con chofer

- Usuario dueno: Carlos
- Vehiculo 1: lo maneja Carlos
- Vehiculo 2: lo maneja Jose

Carlos puede aparecer como `owner_user_id` en ambos vehiculos.
En un servicio Carlos puede ser el operador directo.
En otro servicio Jose puede ser el chofer.
La separacion del dinero no se mezcla porque sale de `service_settlements`.

### Caso 3: empresa con choferes medio tiempo

Un chofer puede tener varias asignaciones activas o por rango de fechas.
Eso se resuelve en `driver_vehicle_assignments` sin reescribir `drivers`.

## Impacto en la app

La app puede seguir trabajando como ahora porque:

- `transport_services` no se rompio
- `vehicle_id` y `driver_id` siguen existiendo
- la placa ya puede mostrarse facil en el dashboard

Despues se pueden exponer endpoints especificos para:

- asignaciones chofer-vehiculo
- liquidaciones por servicio
- reportes por chofer
- reportes por propietario
- resumen por unidad
