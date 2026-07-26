import {
    Activity,
    Award,
    BookOpenCheck,
    Bot,
    Calculator,
    ChartNoAxesCombined,
    CircleGauge,
    ClipboardCheck,
    FileText,
    Download,
    Flag,
    FlaskConical,
    Gamepad2,
    Languages,
    LayoutDashboard,
    LibraryBig,
    ListChecks,
    Medal,
    MessageSquareText,
    Network,
    NotebookTabs,
    Settings,
    ShieldCheck,
    Sparkles,
    Trophy,
    UserCog,
    UsersRound,
    WandSparkles,
} from 'lucide-vue-next';
import type { NavigationGroup } from '../shared/cmsTypes';

export const cmsNavigation: NavigationGroup[] = [
    {
        key: 'dashboard',
        labelKey: 'nav.dashboard',
        items: [
            { key: 'dashboard', labelKey: 'nav.dashboard', icon: LayoutDashboard, permission: 'dashboard.view' },
        ],
    },
    {
        key: 'users',
        labelKey: 'nav.users',
        items: [
            { key: 'students', labelKey: 'nav.students', icon: UsersRound, permission: 'users.view' },
            { key: 'lecturers', labelKey: 'nav.lecturers', icon: BookOpenCheck, permission: 'lecturers.view' },
            { key: 'administrators', labelKey: 'nav.administrators', icon: UserCog, permission: 'users.view' },
        ],
    },
    {
        key: 'learning',
        labelKey: 'nav.learning',
        items: [
            { key: 'math-paths', labelKey: 'nav.paths', icon: Network, permission: 'paths.view', resource: 'math-paths' },
            { key: 'levels', labelKey: 'nav.levels', icon: ListChecks, permission: 'levels.view', resource: 'levels' },
            { key: 'level-prompts', labelKey: 'nav.levelPrompts', icon: WandSparkles, permission: 'levels.view' },
            { key: 'questions', labelKey: 'nav.questions', icon: ClipboardCheck, permission: 'questions.view', resource: 'questions' },
            { key: 'formula-groups', labelKey: 'nav.formulaGroups', icon: LibraryBig, permission: 'formulas.view', resource: 'formula-groups' },
            { key: 'formulas', labelKey: 'nav.formulas', icon: FlaskConical, permission: 'formulas.view', resource: 'formulas' },
            { key: 'formula-explanations', labelKey: 'nav.explanations', icon: MessageSquareText, permission: 'formulas.view' },
            { key: 'calculators', labelKey: 'nav.calculators', icon: Calculator, permission: 'calculators.view', resource: 'calculators' },
            { key: 'practice-templates', labelKey: 'nav.practiceTemplates', icon: NotebookTabs, permission: 'practice_templates.view', resource: 'practice-templates' },
        ],
    },
    {
        key: 'exercise-studio',
        labelKey: 'nav.exerciseStudio',
        items: [
            { key: 'documents', labelKey: 'nav.documents', icon: FileText, permission: 'exercise_documents.view' },
            { key: 'moderation', labelKey: 'nav.moderation', icon: ShieldCheck, permission: 'exercise_documents.moderate' },
            { key: 'generation-logs', labelKey: 'nav.generationLogs', icon: Activity, permission: 'ai.view' },
        ],
    },
    {
        key: 'ai',
        labelKey: 'nav.ai',
        items: [
            { key: 'ai-providers', labelKey: 'nav.providers', icon: Bot, permission: 'ai.view', resource: 'ai-providers' },
            { key: 'ai-models', labelKey: 'nav.models', icon: Sparkles, permission: 'ai.view', resource: 'ai-models' },
            { key: 'ai-prompts', labelKey: 'nav.prompts', icon: WandSparkles, permission: 'ai.view', resource: 'ai-prompts' },
            { key: 'ai-usage', labelKey: 'nav.usage', icon: ChartNoAxesCombined, permission: 'ai.view' },
            { key: 'ai-failures', labelKey: 'nav.failures', icon: Activity, permission: 'ai.view' },
        ],
    },
    {
        key: 'localization',
        labelKey: 'nav.localization',
        items: [
            { key: 'translations', labelKey: 'nav.translationOverview', icon: Languages, permission: 'translations.view' },
            { key: 'missing-translations', labelKey: 'nav.missingTranslations', icon: MessageSquareText, permission: 'translations.view' },
        ],
    },
    {
        key: 'gamification',
        labelKey: 'nav.gamification',
        items: [
            { key: 'xp-rules', labelKey: 'nav.xpRules', icon: CircleGauge, permission: 'gamification.view', resource: 'xp-rules' },
            { key: 'achievements', labelKey: 'nav.achievements', icon: Award, permission: 'gamification.view', resource: 'achievements' },
            { key: 'rewards', labelKey: 'nav.rewards', icon: Medal, permission: 'gamification.view', resource: 'rewards' },
            { key: 'leaderboards', labelKey: 'nav.leaderboards', icon: Trophy, permission: 'gamification.view', resource: 'leaderboards' },
            { key: 'hall-of-fame', labelKey: 'nav.hallOfFame', icon: Trophy, permission: 'gamification.view' },
            { key: 'games', labelKey: 'nav.games', icon: Gamepad2, permission: 'games.view', resource: 'games' },
        ],
    },
    {
        key: 'platform',
        labelKey: 'nav.platform',
        items: [
            { key: 'feature-flags', labelKey: 'nav.featureFlags', icon: Flag, permission: 'settings.view', resource: 'feature-flags' },
            { key: 'settings', labelKey: 'nav.settings', icon: Settings, permission: 'settings.view' },
            { key: 'audit-log', labelKey: 'nav.audit', icon: ShieldCheck, permission: 'audit.view' },
            { key: 'exports', labelKey: 'nav.exports', icon: Download, permission: 'exports.create' },
        ],
    },
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
