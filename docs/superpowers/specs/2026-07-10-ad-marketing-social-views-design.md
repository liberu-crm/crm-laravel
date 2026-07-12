# Ad / MarketingCampaign / SocialMediaPost detail Views — design (2026-07-10)

Three independent read-only detail-View slices toward the 1.16.0 cut, completing
detail-view coverage for the advertising/marketing records (alongside Campaign,
AdSet, AdvertisingAccount). Each repeats the established `ViewRecord` + infolist
pattern; one PR per resource; none are masked (plain infolists).

- **Slice A — Ad** (#): `ViewAd` — name, headline, status, account/campaign/ad-set
  relations, created.
- **Slice B — MarketingCampaign** (#): `ViewMarketingCampaign` — name, type,
  status, subject, scheduled, content, created.
- **Slice C — SocialMediaPost** (#): `ViewSocialMediaPost` — content, status,
  platforms (badge — array cast), link, scheduled, created.

Each adds a `ViewAction` (first record action) + a `view` route; access is
already permission-gated by the `EnforcesResourcePermissions` trait. No schema
changes → MySQL parity by construction. Each slice = one PR, rc of 1.16.0.
