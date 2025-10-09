# API Data Relationships - Complete Summary

## ✅ All Relationships Established

### 1. Equipment Relationships

```php
// Equipment Model
$equipment->plant                    // BelongsTo Plant
$equipment->station                  // BelongsTo Station
$equipment->equipmentGroup           // BelongsTo EquipmentGroup
$equipment->runningTimes             // HasMany RunningTime
$equipment->workOrders               // HasMany WorkOrder
$equipment->equipmentWorkOrders      // HasMany EquipmentWorkOrder
$equipment->equipmentMaterials       // HasMany EquipmentMaterial
```

### 2. Running Time Relationships

```php
// RunningTime Model
$runningTime->plant                  // BelongsTo Plant
$runningTime->equipment              // BelongsTo Equipment
```

### 3. Work Order Relationships

```php
// WorkOrder Model
$workOrder->plant                    // BelongsTo Plant
$workOrder->station                  // BelongsTo Station
$workOrder->equipment                // BelongsTo Equipment
$workOrder->equipmentWorkOrders      // HasMany EquipmentWorkOrder
```

### 4. Equipment Work Order Relationships

```php
// EquipmentWorkOrder Model
$equipmentWorkOrder->plant           // BelongsTo Plant
$equipmentWorkOrder->equipment       // BelongsTo Equipment
$equipmentWorkOrder->workOrder       // BelongsTo WorkOrder
$equipmentWorkOrder->equipmentMaterialsByReservation  // HasMany EquipmentMaterial (by reservation)
$equipmentWorkOrder->equipmentMaterialsByMaterial     // HasMany EquipmentMaterial (by material)
```

### 5. Equipment Material Relationships

```php
// EquipmentMaterial Model
$equipmentMaterial->plant            // BelongsTo Plant
$equipmentMaterial->equipment        // BelongsTo Equipment
$equipmentMaterial->equipmentWorkOrderByReservation   // BelongsTo EquipmentWorkOrder (by reservation)
$equipmentMaterial->equipmentWorkOrderByMaterial      // BelongsTo EquipmentWorkOrder (by material)
```

## 🔗 Foreign Key Constraints

| From Table            | From Column        | To Table              | To Column        | Action   |
| --------------------- | ------------------ | --------------------- | ---------------- | -------- |
| running_times         | equipment_number   | equipment             | equipment_number | CASCADE  |
| work_orders           | equipment_number   | equipment             | equipment_number | SET NULL |
| equipment_work_orders | equipment_number   | equipment             | equipment_number | SET NULL |
| equipment_work_orders | order_number       | work_orders           | order            | SET NULL |
| equipment_materials   | equipment_number   | equipment             | equipment_number | SET NULL |
| equipment_materials   | reservation_number | equipment_work_orders | reservation      | SET NULL |

## 📊 Data Flow Examples

### Get all running times for an equipment:

```php
$equipment = Equipment::find(1);
$runningTimes = $equipment->runningTimes;
```

### Get all work orders for an equipment:

```php
$equipment = Equipment::find(1);
$workOrders = $equipment->workOrders;
```

### Get equipment work orders for a specific work order:

```php
$workOrder = WorkOrder::find(1);
$equipmentWorkOrders = $workOrder->equipmentWorkOrders;
```

### Get equipment materials for an equipment work order:

```php
$equipmentWorkOrder = EquipmentWorkOrder::find(1);
$materialsByReservation = $equipmentWorkOrder->equipmentMaterialsByReservation;
$materialsByMaterial = $equipmentWorkOrder->equipmentMaterialsByMaterial;
```

### Get equipment work order from equipment material:

```php
$equipmentMaterial = EquipmentMaterial::find(1);
$equipmentWorkOrderByReservation = $equipmentMaterial->equipmentWorkOrderByReservation;
$equipmentWorkOrderByMaterial = $equipmentMaterial->equipmentWorkOrderByMaterial;
```

## 🎯 Key Relationships Clarified

1. **Equipment Work Orders → Work Orders**: `order_number` → `order`
2. **Equipment Materials → Equipment Work Orders**: `reservation_number` → `reservation`
3. **Equipment Materials → Equipment Work Orders**: `material_number` → `material`
4. **All entities → Equipment**: `equipment_number` → `equipment_number`

## ✅ Data Integrity

- All orphaned records cleaned up
- Foreign key constraints enforce referential integrity
- Proper cascade/set null actions configured
- All relationships properly indexed for performance

**Result: Complete API data relationship mapping with full referential integrity!** 🚀
