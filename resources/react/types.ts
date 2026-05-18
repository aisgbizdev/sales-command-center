export type Option = { value: string; label: string };

export type MetaResponse = {
  user: { name: string; roleLabel: string; initials: string };
  navigation: { label: string; href: string }[];
  abilities: {
    accessPerformance: boolean;
    accessChatReviews: boolean;
    accessKnowledgeQueue: boolean;
    manageUsers: boolean;
    manageSystemSettings: boolean;
  };
};

export type TeamOption = Option & { unitId: string; unitName: string };

export type SalesDisciplineMetric = {
  sales_id: number;
  sales_name: string;
  active_lead_count: number;
  follow_up_lead_count: number;
  overdue_lead_count: number;
  stale_lead_count: number;
  follow_up_compliance_rate: number;
  overdue_ratio: number;
  stale_lead_ratio: number;
  avg_update_delay_hours: number;
  crm_activity_score: number;
  daily_activity_count: number;
  health_state: string;
};

export type ObjectionMetric = {
  objection: string;
  label: string;
  total: number;
};

export type ObjectionConversionMetric = ObjectionMetric & {
  closing: number;
  conversionRate: number;
};

export type HighRiskObjectionMetric = ObjectionMetric & {
  conversionRate: number;
  lostRate: number;
  riskScore: number;
};

export type ObjectionInsightsResponse = {
  topObjections: ObjectionMetric[];
  objectionByCategory: {
    category: string;
    label: string;
    items: ObjectionMetric[];
  }[];
  objectionConversion: ObjectionConversionMetric[];
  objectionBySource: {
    source: string;
    label: string;
    items: (ObjectionMetric & { percent: number })[];
  }[];
  highRiskObjections: HighRiskObjectionMetric[];
  managerInsights: {
    mostCommonThisWeek: ObjectionMetric | null;
    lowestConversion: HighRiskObjectionMetric | null;
    regularAccountTopObjection: ObjectionMetric | null;
  };
};

export type ManagerInsightsResponse = {
  teamHealth: {
    totalActiveLeads: number;
    overdueLeads: number;
    staleLeads: number;
    dueToday: number;
    activeSalesToday: number;
    inactiveSalesToday: number;
    avgFollowUpCompliance: number;
    avgCrmActivityScore: number;
  };
  alerts: { level: string; message: string }[];
  salesRanking: {
    topDisciplined: SalesDisciplineMetric[];
    needsAttention: SalesDisciplineMetric[];
  };
  pipelineBottleneck: {
    status: string;
    label: string;
    total: number;
    stuckCount: number;
    percent: number;
  }[];
  priorityLeads: {
    id: number;
    prospectCode: string;
    name: string;
    owner: string;
    status: string;
    statusLabel: string;
    overdueDays: number;
    nextFollowUpDateLabel: string;
    priorityLevel: string;
    detailUrl: string;
  }[];
  objectionTrends: {
    mostCommon: ObjectionMetric | null;
    worstConversion: HighRiskObjectionMetric | null;
    trendingUp: ObjectionMetric[];
  };
};

export type UsersResponse = {
  items: {
    id: number;
    name: string;
    email: string;
    role: string;
    roleLabel: string;
    unitId: string;
    unitName: string;
    teamId: string;
    teamName: string;
    createdAtLabel: string;
    usageCount: number;
    canDelete: boolean;
  }[];
  meta: { currentPage: number; lastPage: number; perPage: number; total: number };
  filters: {
    current: Record<string, string>;
    roles: Option[];
    units: Option[];
    teams: TeamOption[];
  };
};

export type UserMasterDataResponse = {
  roles: {
    id: number;
    code: string;
    label: string;
    description: string | null;
    sortOrder: number;
    isSystem: boolean;
    usageCount: number;
    canDelete: boolean;
  }[];
  units: {
    id: number;
    name: string;
    code: string;
    usageCount: number;
    canDelete: boolean;
  }[];
  teams: {
    id: number;
    name: string;
    code: string;
    unitId: string;
    unitName: string;
    usageCount: number;
    canDelete: boolean;
  }[];
};

export type DashboardResponse = {
  kpis: {
    totalProspects: number;
    miniProspects: number;
    regularProspects: number;
    openProspects: number;
    wonProspects: number;
    bridgeCandidatesCount: number;
    bridgeMovedCount: number;
    miniConversionPercent: number;
    regularConversionPercent: number;
    todayInputCount: number;
    overdueCount: number;
    dueTodayCount: number;
    staleCount: number;
    staleOverdueCount: number;
    highPriorityCount: number;
    agingOverSevenDaysCount: number;
    crmHealthScore: number;
    overdueRatio: number;
    activeSalesTodayCount: number;
    healthPercent: number;
  };
  statusSummary: { key: string; label: string; total: number }[];
  upcomingFollowUp: {
    id: number;
    prospectCode: string;
    name: string;
    owner: string;
    nextFollowUpDateLabel: string;
    status: string;
    statusLabel: string;
    aging_days: number;
    last_activity_diff: string;
    is_stale: boolean;
    status_updated_at: string | null;
    last_activity_at: string | null;
    follow_up_state: string;
    priority_level: string;
    overdue_days: number;
    detailUrl: string;
  }[];
  lostReasonSummary: { key: string; label: string; total: number }[];
  disciplineSnapshot: {
    topOverdueSales: SalesDisciplineMetric[];
    mostDisciplinedSales: SalesDisciplineMetric[];
    salesWithoutActivityToday: SalesDisciplineMetric[];
  };
  filters: {
    current: Record<string, string>;
    accountCategories: Option[];
    gptModes: Option[];
    userTemperatures: Option[];
    dominantEmotions: Option[];
    bridgeStatuses: Option[];
    lostReasons: Option[];
    salesUsers: Option[];
  };
};

export type ProspectsResponse = {
  items: {
    id: number;
    prospectCode: string;
    name: string;
    company: string;
    accountCategoryLabel: string;
    gptModeLabel: string;
    userTemperatureLabel: string;
    dominantEmotionLabel: string;
    mainObjection: string | null;
    bridgeCandidate: boolean;
    bridgeStatusLabel: string;
    lostReasonLabel: string;
    owner: string;
    team: string;
    unit: string;
    status: string;
    statusLabel: string;
    nextFollowUpDateLabel: string;
    isOverdue: boolean;
    aging_days: number;
    last_activity_diff: string;
    is_stale: boolean;
    status_updated_at: string | null;
    last_activity_at: string | null;
    follow_up_state: string;
    priority_level: string;
    overdue_days: number;
    showUrl: string;
    editUrl: string;
    canEdit: boolean;
  }[];
  meta: { currentPage: number; lastPage: number; perPage: number; total: number };
  filters: {
    current: Record<string, string>;
    statuses: Option[];
    accountCategories: Option[];
    gptModes: Option[];
    userTemperatures: Option[];
    dominantEmotions: Option[];
    bridgeStatuses: Option[];
    lostReasons: Option[];
    salesUsers: Option[];
  };
  permissions: { canCreateProspect: boolean; createUrl: string };
};

export type PipelineResponse = {
  columns: {
    status: string;
    label: string;
    count: number;
    items: {
      id: number;
      prospectCode: string;
      name: string;
      company: string;
      owner: string;
      accountCategoryLabel: string;
      gptMode: string | null;
      gptModeLabel: string;
      userTemperature: string | null;
      userTemperatureLabel: string;
      dominantEmotion: string | null;
      dominantEmotionLabel: string;
      mainObjection: string | null;
      bridgeCandidate: boolean;
      bridgeStatus: string;
      bridgeStatusLabel: string;
      status: string;
      statusLabel: string;
      nextFollowUpDate: string | null;
      nextFollowUpDateLabel: string;
      isOverdue: boolean;
      aging_days: number;
      last_activity_diff: string;
      is_stale: boolean;
      status_updated_at: string | null;
      last_activity_at: string | null;
      follow_up_state: string;
      priority_level: string;
      overdue_days: number;
      quickUpdateUrl: string;
      detailUrl: string;
      canEdit: boolean;
    }[];
  }[];
  metrics: { overdueCount: number; dueTodayCount: number; dueSoonCount: number };
  filters: {
    current: Record<string, string>;
    accountCategories: Option[];
    gptModes: Option[];
    userTemperatures: Option[];
    dominantEmotions: Option[];
    bridgeStatuses: Option[];
    lostReasons: Option[];
    salesUsers: Option[];
  };
};

export type PerformanceResponse = {
  rows: {
    id: number;
    name: string;
    totalProspects: number;
    closingCount: number;
    miniClosingCount: number;
    regularClosingCount: number;
    bridgeConversionCount: number;
    lostCount: number;
    activityCount: number;
    overdueCount: number;
    totalValueLabel: string;
    ratio: number;
    discipline: SalesDisciplineMetric | null;
  }[];
  operationalDiscipline: SalesDisciplineMetric[];
  managerInsights: {
    topOverdueSales: SalesDisciplineMetric[];
    mostDisciplinedSales: SalesDisciplineMetric[];
    salesWithoutActivityToday: SalesDisciplineMetric[];
  };
  statusBreakdown: { key: string; label: string; total: number }[];
  overdueProspects: {
    id: number;
    prospectCode: string;
    name: string;
    owner: string;
    status: string;
    statusLabel: string;
    nextFollowUpDateLabel: string;
    aging_days: number;
    last_activity_diff: string;
    is_stale: boolean;
    status_updated_at: string | null;
    last_activity_at: string | null;
    follow_up_state: string;
    priority_level: string;
    overdue_days: number;
    detailUrl: string;
  }[];
  objectionFrequency: { label: string; total: number }[];
  filters: {
    current: Record<string, string> & { from: string; to: string };
    accountCategories: Option[];
    gptModes: Option[];
    userTemperatures: Option[];
    dominantEmotions: Option[];
    bridgeStatuses: Option[];
    lostReasons: Option[];
    salesUsers: Option[];
  };
};

export type ChatReviewsResponse = {
  items: {
    id: number;
    title: string;
    channel: string;
    customerName: string;
    customerCompany: string;
    outcome: string;
    outcomeLabel: string;
    status: string;
    statusLabel: string;
    summary: string;
    suggestedKnowledgeUpdate: string | null;
    objectionType: string | null;
    objectionTypeLabel: string | null;
    emotionalState: string | null;
    emotionalStateLabel: string | null;
    submitter: string;
    submitterRole: string;
    prospectName: string;
    prospectCode: string;
    accountCategory: string | null;
    accountCategoryLabel: string;
    managerNotesCount: number;
    knowledgeQueueCount: number;
    showUrl: string;
    addManagerNoteUrl: string;
    markImportantUrl: string;
    canComment: boolean;
    canMarkImportant: boolean;
  }[];
  meta: { currentPage: number; lastPage: number; perPage: number; total: number };
  filters: {
    current: Record<string, string>;
    outcomes: Option[];
    statuses: Option[];
    accountCategories: Option[];
  };
  permissions: { canCreateChatReview: boolean; createUrl: string };
};

export type KnowledgeQueueResponse = {
  items: {
    id: number;
    priority: string;
    priorityLabel: string;
    status: string;
    statusLabel: string;
    problemPattern: string;
    recommendedUpdate: string;
    expectedImpact: string | null;
    superAdminNote: string | null;
    reviewedAtLabel: string;
    chatReviewTitle: string;
    chatReviewOutcome: string;
    chatReviewStatus: string;
    prospectName: string;
    prospectCode: string;
    accountCategoryLabel: string;
    requester: string;
    reviewer: string;
    setReviewUrl: string;
    approveUrl: string;
    rejectUrl: string;
    canReview: boolean;
  }[];
  meta: { currentPage: number; lastPage: number; perPage: number; total: number };
  filters: {
    current: Record<string, string>;
    statuses: Option[];
    priorities: Option[];
    accountCategories: Option[];
  };
};

export type ProspectDetailResponse = {
  prospect: {
    id: number;
    prospectCode: string;
    name: string;
    company: string;
    phone: string | null;
    email: string | null;
    source: string | null;
    owner: string;
    ownerId: string;
    accountCategory: string;
    accountCategoryLabel: string;
    status: string;
    statusLabel: string;
    gptMode: string | null;
    userTemperature: string | null;
    dominantEmotion: string | null;
    bridgeCandidate: boolean;
    bridgeStatus: string | null;
    lostReason: string | null;
    mainObjection: string | null;
    priority: number;
    nextFollowUpDate: string | null;
    nextFollowUpDateLabel: string;
    estimationValue: number;
    estimationValueLabel: string;
    notes: string | null;
    aging_days: number;
    last_activity_diff: string;
    is_stale: boolean;
    status_updated_at: string | null;
    last_activity_at: string | null;
    follow_up_state: string;
    priority_level: string;
    overdue_days: number;
  };
  logs: {
    id: number;
    dateLabel: string;
    activityType: string;
    activityTypeLabel: string;
    summary: string;
    result: string;
    objectionType: string | null;
    objectionTypeLabel: string | null;
    objectionDetail: string | null;
    emotionalState: string | null;
    emotionalStateLabel: string | null;
    user: string;
  }[];
  canEdit: boolean;
  editUrl: string;
  updateUrl: string;
  storeLogUrl: string;
  types: Option[];
  objectionTypes: Option[];
  emotionalStates: Option[];
};

export type ProspectFormResponse = {
  sources: Option[];
  statuses: Option[];
  types: Option[];
  canAssignOwner: boolean;
  accountCategories: Option[];
  gptModes: Option[];
  userTemperatures: Option[];
  dominantEmotions: Option[];
  bridgeStatuses: Option[];
  lostReasons: Option[];
  salesUsers: Option[];
};

export type ChatReviewDetailResponse = {
  review: {
    id: number;
    title: string;
    channel: string;
    outcome: string;
    objectionType: string | null;
    objectionDetail: string | null;
    emotionalState: string | null;
    status: string;
    customerName: string;
    customerCompany: string | null;
    prospectId: string;
    chatSummary: string;
    chatExcerpt: string | null;
    whatWorked: string | null;
    whatFailed: string | null;
    suggestedKnowledgeUpdate: string | null;
    submitter: string;
    submitterRole: string;
    prospectCode: string;
    prospectName: string;
    accountCategoryLabel: string;
  };
  canEdit: boolean;
  editUrl: string;
  updateUrl: string;
};

export type ChatReviewFormResponse = {
  channels: Option[];
  outcomes: Option[];
  statuses: Option[];
  prospects: Option[];
  objectionTypes: Option[];
  emotionalStates: Option[];
};
