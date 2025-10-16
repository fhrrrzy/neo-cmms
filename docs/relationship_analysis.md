# API Data Relationships Analysis

## Current Relationships ✅

### Core Entity Relationships

```
plants (1) ←→ (N) equipment
plants (1) ←→ (N) stations
plants (1) ←→ (N) running_times
plants (1) ←→ (N) work_orders
plants (1) ←→ (N) equipment_work_orders
plants (1) ←→ (N) equipment_materials

stations (1) ←→ (N) equipment
stations (1) ←→ (N) work_orders

equipment_groups (1) ←→ (N) equipment
```

## Missing Relationships ❌

### Equipment Number Relationships

```
equipment (1) ←→ (N) running_times
equipment (1) ←→ (N) work_orders
equipment (1) ←→ (N) equipment_work_orders
equipment (1) ←→ (N) equipment_materials
```

### Order/Reservation/Material Relationships

```
work_orders (1) ←→ (N) equipment_work_orders  [via order_number → order]
equipment_work_orders (1) ←→ (N) equipment_materials  [via reservation_number → reservation]
equipment_work_orders (1) ←→ (N) equipment_materials  [via material_number → material]
```

## Data Flow Analysis

### Equipment API

- **Primary Key**: equipment_number
- **Relationships**: Links to plant, station, equipment_group
- **Used by**: All other APIs reference equipment_number

### Running Time API

- **Primary Key**: ims_id
- **Equipment Link**: equipment_number → equipment.equipment_number
- **Plant Link**: plant_id → plants.id

### Work Orders API

- **Primary Key**: order
- **Equipment Link**: equipment_number → equipment.equipment_number
- **Plant Link**: plant_id → plants.id
- **Station Link**: station_id → stations.id

### Equipment Work Orders API

- **Primary Key**: ims_id
- **Equipment Link**: equipment_number → equipment.equipment_number
- **Plant Link**: plant_id → plants.id
- **Work Order Link**: order_number → work_orders.order

### Equipment Materials API

- **Primary Key**: ims_id
- **Equipment Link**: equipment_number → equipment.equipment_number
- **Plant Link**: plant_id → plants.id
- **Reservation Link**: reservation_number → equipment_work_orders.reservation

## Missing Foreign Key Constraints

1. **running_times.equipment_number** → **equipment.equipment_number**
2. **work_orders.equipment_number** → **equipment.equipment_number**
3. **equipment_work_orders.equipment_number** → **equipment.equipment_number**
4. **equipment_work_orders.order_number** → **work_orders.order**
5. **equipment_materials.equipment_number** → **equipment.equipment_number**
6. **equipment_materials.reservation_number** → **equipment_work_orders.reservation**

## Recommendations

### 1. Add Foreign Key Constraints

Create migrations to add foreign key constraints for data integrity.

### 2. Add Relationship Methods

Add Eloquent relationship methods to models for easier querying.

### 3. Add Indexes

Ensure proper indexing on relationship fields for performance.

### 4. Data Validation

Add validation to ensure equipment_number exists before creating related records.
