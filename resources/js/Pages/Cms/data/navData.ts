import {
  Activity,
  LayoutDashboard,
  LibraryBig,
  ListChecks,
  Settings,
  UsersRound,
} from '@lucide/vue';

export interface CmsSection {
  key: string;
  label: string;
  icon: any;
}

export const cmsSections: CmsSection[] = [
  { key: 'overview', label: 'Overview', icon: LayoutDashboard },
  { key: 'users', label: 'Users', icon: UsersRound },
  { key: 'formulas', label: 'Formulas', icon: LibraryBig },
  { key: 'levels', label: 'Math Levels', icon: ListChecks },
  { key: 'reports', label: 'Explanations', icon: Activity },
  { key: 'settings', label: 'Settings', icon: Settings },
];

export const roleOptions = ['admin', 'lecturer', 'student'];
export const formulaLocales = ['en', 'hy', 'ru', 'fr'];

export const cacheFilterOptions = [
  { value: 'all', label: 'All cache' },
  { value: 'cached', label: 'Cached here' },
  { value: 'missing', label: 'Missing here' },
  { value: 'cached_any', label: 'Cached anywhere' },
  { value: 'missing_any', label: 'No cache' },
];

export const aiUsage = {
  percent: 62,
  generations: 184,
  creditsUsed: '31.2k',
  creditLimit: '50k',
  items: [
    { label: 'Formula translations', value: 86 },
    { label: 'Level prompts', value: 64 },
    { label: 'Quiz drafts', value: 34 },
  ],
};
