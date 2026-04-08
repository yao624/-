import FilterSection from './FilterSection.vue';
import type {
  FilterFieldType,
  FilterFieldOption,
  FilterFieldConfig,
  FilterValue,
} from './types';

// Main component export
export { FilterSection };

// Type exports
export type {
  FilterFieldType,
  FilterFieldOption,
  FilterFieldConfig,
  FilterValue,
};

// Default export
export default FilterSection;

// Export component instance type for TypeScript support
export type FilterSectionInstance = InstanceType<typeof FilterSection>;
