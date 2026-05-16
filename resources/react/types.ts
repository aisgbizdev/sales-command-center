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
    detailUrl: string;
  }[];
  lostReasonSummary: { key: string; label: string; total: number }[];
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
  }[];
  statusBreakdown: { key: string; label: string; total: number }[];
  overdueProspects: {
    id: number;
    prospectCode: string;
    name: string;
    owner: string;
    status: string;
    statusLabel: string;
    nextFollowUpDateLabel: string;
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
  };
  logs: {
    id: number;
    dateLabel: string;
    activityType: string;
    activityTypeLabel: string;
    summary: string;
    result: string;
    user: string;
  }[];
  canEdit: boolean;
  editUrl: string;
  updateUrl: string;
  storeLogUrl: string;
  types: Option[];
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
};
