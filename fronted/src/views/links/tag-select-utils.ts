export interface LinkMetaTagFolder {
  id: number;
  name: string;
}

export interface LinkMetaTag {
  id: number;
  folder_id: number;
  name: string;
}

export interface LinkMetaTagOption {
  id: number;
  name: string;
  tag_id: number | null;
  children?: LinkMetaTagOption[];
}

export interface LinkMetaTagTreePayload {
  tagFolders: LinkMetaTagFolder[];
  tags: LinkMetaTag[];
  tagOptions: LinkMetaTagOption[];
}

const walkOptions = (
  options: LinkMetaTagOption[],
  visitor: (option: LinkMetaTagOption) => void,
) => {
  options.forEach((option) => {
    visitor(option);
    if (Array.isArray(option.children) && option.children.length) {
      walkOptions(option.children, visitor);
    }
  });
};

export const normalizeMetaTagTreePayload = (raw: any): LinkMetaTagTreePayload => ({
  tagFolders: Array.isArray(raw?.tagFolders) ? raw.tagFolders : [],
  tags: Array.isArray(raw?.tags) ? raw.tags : [],
  tagOptions: Array.isArray(raw?.tagOptions) ? raw.tagOptions : [],
});

export const resolveOptionIdsByNames = (
  names: string[],
  options: LinkMetaTagOption[],
): number[] => {
  const optionIdsByName = new Map<string, number[]>();

  walkOptions(options, (option) => {
    const key = String(option.name ?? '').trim();
    if (!key) return;
    const list = optionIdsByName.get(key) ?? [];
    list.push(Number(option.id));
    optionIdsByName.set(key, list);
  });

  return Array.from(
    new Set(
      names
        .map((name) => String(name ?? '').trim())
        .filter(Boolean)
        .flatMap((name) => optionIdsByName.get(name) ?? []),
    ),
  ).filter((id) => Number.isFinite(id));
};

export const resolveNamesByOptionIds = (
  optionIds: number[],
  options: LinkMetaTagOption[],
): string[] => {
  const optionNameById = new Map<number, string>();

  walkOptions(options, (option) => {
    const id = Number(option.id);
    const name = String(option.name ?? '').trim();
    if (Number.isFinite(id) && name) {
      optionNameById.set(id, name);
    }
  });

  return Array.from(
    new Set(
      optionIds
        .map((id) => optionNameById.get(Number(id)) ?? '')
        .map((name) => name.trim())
        .filter(Boolean),
    ),
  );
};
