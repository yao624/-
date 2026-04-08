import { ref, watch } from 'vue';

export const useTableHeight = (gap: number = 392) => {
  const tableHeight = ref(window.innerHeight - gap);
  const scroll = ref({ y: tableHeight.value });
  watch(
    () => window.innerHeight,
    value => (tableHeight.value = value - gap),
  );

  return {
    tableHeight,
    scroll,
  };
};
