# Data Integrity Report
Generated: 2026-03-14 16:24:44 UTC

## Summary
- **Total projects scanned:** 160
- **Total issues found:** 161
- **Critical issues:** 130
- **Warning issues:** 31
- **Info issues:** 0

### Per-AHJ Breakdown
- **City of Solarville**: 158 issues (Critical: 127, Warning: 31, Info: 0)
- **County of Sunridge**: 3 issues (Critical: 3, Warning: 0, Info: 0)

## Findings

### Critical
_Issues that would cause application errors or data corruption_

- **Impossible Timestamp**: Project ID 251 ('400 Elm St - 9kW PV') has approved_at (2024-06-14 10:00:00) before submitted_at (2024-06-15 14:30:00)
- **Impossible Timestamp**: Project ID 101 ('6581 Washington Blvd - 14kW ST') has submitted_at (2024-12-03 07:12:02) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 102 ('5605 Washington Blvd - 8kW BIPV') has submitted_at (2024-12-08 21:28:55) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 104 ('101 Maple Dr - 13kW ST') has submitted_at (2024-08-06 13:17:42) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 105 ('9767 Maple Dr - 12kW ST') has submitted_at (2024-12-25 05:57:23) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 106 ('7549 Oak Ave - 10kW ST') has submitted_at (2024-05-23 16:00:24) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 107 ('2233 Main St - 12kW BIPV') has submitted_at (2024-03-28 06:24:30) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 108 ('9772 Washington Blvd - 14kW ST') has submitted_at (2024-08-26 10:51:22) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 109 ('9631 Maple Dr - 14kW ST') has submitted_at (2024-10-11 02:21:23) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 110 ('7751 Maple Dr - 4kW BIPV') has submitted_at (2024-02-12 08:48:16) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 111 ('496 Maple Dr - 9kW BIPV') has submitted_at (2024-04-02 07:42:28) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 113 ('1122 Pine Ln - 3kW PV+ST') has submitted_at (2024-03-24 03:52:29) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 118 ('2286 Oak Ave - 4kW BIPV') has submitted_at (2024-01-06 13:33:34) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 120 ('4985 Oak Ave - 7kW ST') has submitted_at (2024-09-24 04:34:46) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 121 ('8789 Elm St - 6kW PV+ST') has submitted_at (2024-04-26 11:11:28) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 122 ('6164 Maple Dr - 4kW PV+ST') has submitted_at (2024-02-13 01:13:05) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 123 ('3205 Elm St - 7kW BIPV') has submitted_at (2024-06-11 21:15:51) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 124 ('7293 Elm St - 4kW ST') has submitted_at (2024-05-02 16:50:43) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 125 ('4299 Maple Dr - 15kW ST') has submitted_at (2024-04-01 20:04:42) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 126 ('8619 Pine Ln - 5kW ST') has submitted_at (2024-11-29 10:28:55) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 127 ('6688 Maple Dr - 15kW PV+ST') has submitted_at (2024-08-18 08:19:24) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 128 ('5380 Cedar Rd - 10kW BIPV') has submitted_at (2024-04-29 10:46:08) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 129 ('908 Elm St - 3kW ST') has submitted_at (2024-12-12 16:16:07) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 130 ('8662 Pine Ln - 3kW ST') has submitted_at (2024-01-18 20:02:11) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 131 ('3633 Maple Dr - 15kW PV') has submitted_at (2024-12-11 05:32:44) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 132 ('7213 Washington Blvd - 6kW PV+ST') has submitted_at (2024-09-06 22:15:26) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 133 ('1830 Main St - 12kW ST') has submitted_at (2024-07-14 02:16:17) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 134 ('6696 Oak Ave - 9kW BIPV') has submitted_at (2024-12-25 21:05:01) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 136 ('9281 Main St - 13kW PV+ST') has submitted_at (2024-02-09 16:32:17) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 137 ('5358 Oak Ave - 8kW ST') has submitted_at (2024-05-03 23:46:49) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 138 ('5379 Maple Dr - 12kW ST') has submitted_at (2024-07-19 02:47:55) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 139 ('2631 Main St - 6kW ST') has submitted_at (2024-09-22 18:38:54) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 140 ('4752 Cedar Rd - 7kW BIPV') has submitted_at (2024-08-19 23:08:38) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 141 ('8107 Elm St - 9kW ST') has submitted_at (2024-06-06 10:57:40) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 142 ('4019 Main St - 10kW ST') has submitted_at (2024-09-14 00:22:04) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 145 ('1574 Maple Dr - 9kW BIPV') has submitted_at (2025-01-13 10:21:11) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 146 ('5007 Main St - 12kW BIPV') has submitted_at (2025-01-21 02:34:59) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 147 ('4856 Washington Blvd - 11kW PV') has submitted_at (2024-11-06 23:27:49) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 149 ('3201 Oak Ave - 3kW PV+ST') has submitted_at (2024-01-13 12:28:13) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 150 ('1140 Oak Ave - 12kW ST') has submitted_at (2024-11-04 10:17:24) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 151 ('6956 Main St - 15kW BIPV') has submitted_at (2024-03-03 14:42:41) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 153 ('1221 Main St - 8kW PV') has submitted_at (2024-01-03 07:41:20) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 154 ('4394 Cedar Rd - 5kW PV') has submitted_at (2024-03-20 00:00:25) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 155 ('8613 Main St - 12kW ST') has submitted_at (2024-12-24 02:44:34) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 156 ('4339 Maple Dr - 7kW ST') has submitted_at (2024-04-19 22:43:29) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 157 ('3830 Main St - 5kW PV+ST') has submitted_at (2024-12-28 10:23:47) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 158 ('8056 Washington Blvd - 7kW PV+ST') has submitted_at (2024-07-16 05:33:06) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 159 ('4553 Washington Blvd - 11kW PV+ST') has submitted_at (2024-05-18 07:36:25) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 160 ('8988 Pine Ln - 9kW ST') has submitted_at (2024-09-26 16:59:11) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 161 ('8793 Elm St - 6kW PV') has submitted_at (2024-10-01 17:32:57) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 162 ('7116 Oak Ave - 7kW ST') has submitted_at (2024-12-07 23:10:29) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 163 ('8552 Elm St - 3kW PV+ST') has submitted_at (2024-01-21 10:49:32) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 165 ('5417 Pine Ln - 14kW PV') has submitted_at (2024-06-08 23:56:36) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 166 ('2077 Washington Blvd - 4kW ST') has submitted_at (2024-10-16 23:30:32) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 170 ('9545 Main St - 15kW ST') has submitted_at (2024-03-16 19:15:48) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 171 ('1506 Elm St - 3kW ST') has submitted_at (2024-05-23 20:57:46) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 172 ('4798 Elm St - 12kW BIPV') has submitted_at (2024-04-27 11:01:10) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 174 ('6502 Oak Ave - 15kW PV') has submitted_at (2024-10-14 04:33:21) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 175 ('7867 Main St - 10kW PV+ST') has submitted_at (2024-01-01 22:09:13) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 176 ('4054 Maple Dr - 8kW PV') has submitted_at (2024-07-31 02:17:20) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 177 ('490 Elm St - 12kW BIPV') has submitted_at (2024-03-09 04:43:26) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 178 ('3910 Pine Ln - 4kW PV') has submitted_at (2024-11-09 05:20:01) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 179 ('6391 Cedar Rd - 8kW PV') has submitted_at (2024-06-26 13:24:43) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 180 ('233 Washington Blvd - 5kW PV') has submitted_at (2024-02-24 16:07:11) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 181 ('9708 Oak Ave - 6kW PV') has submitted_at (2024-12-03 11:31:38) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 182 ('3406 Maple Dr - 11kW PV+ST') has submitted_at (2024-10-28 21:54:56) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 183 ('6307 Oak Ave - 13kW BIPV') has submitted_at (2024-03-01 11:13:09) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 184 ('4652 Main St - 9kW PV+ST') has submitted_at (2024-08-20 17:14:14) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 185 ('5762 Cedar Rd - 7kW ST') has submitted_at (2024-02-24 19:05:44) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 187 ('7018 Maple Dr - 3kW BIPV') has submitted_at (2024-11-28 01:45:05) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 188 ('7551 Pine Ln - 14kW PV') has submitted_at (2024-05-20 00:28:31) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 190 ('3749 Washington Blvd - 13kW BIPV') has submitted_at (2025-01-01 01:42:56) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 191 ('6457 Elm St - 13kW ST') has submitted_at (2024-01-15 11:07:20) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 192 ('9952 Main St - 14kW PV') has submitted_at (2024-09-17 20:40:24) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 193 ('7244 Elm St - 13kW PV') has submitted_at (2024-10-06 19:54:24) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 194 ('4667 Pine Ln - 9kW BIPV') has submitted_at (2024-09-07 04:27:24) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 195 ('7907 Washington Blvd - 13kW PV+ST') has submitted_at (2024-10-24 17:26:43) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 196 ('4248 Pine Ln - 9kW BIPV') has submitted_at (2024-02-17 07:28:33) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 198 ('7553 Pine Ln - 11kW ST') has submitted_at (2024-05-13 23:37:41) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 199 ('8001 Washington Blvd - 6kW BIPV') has submitted_at (2024-06-09 23:07:00) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 200 ('9872 Cedar Rd - 9kW PV') has submitted_at (2024-07-27 01:09:43) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 201 ('1207 Cedar Rd - 7kW PV') has submitted_at (2024-09-19 08:30:15) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 204 ('4984 Cedar Rd - 6kW PV') has submitted_at (2024-08-28 01:45:38) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 205 ('289 Main St - 10kW PV+ST') has submitted_at (2024-02-25 04:14:23) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 206 ('9910 Washington Blvd - 7kW PV+ST') has submitted_at (2024-12-02 00:17:52) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 207 ('6500 Main St - 13kW BIPV') has submitted_at (2024-05-24 14:35:07) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 208 ('3949 Elm St - 14kW PV+ST') has submitted_at (2024-03-06 14:33:09) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 209 ('6389 Elm St - 9kW PV') has submitted_at (2024-03-28 09:25:06) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 211 ('8092 Cedar Rd - 12kW BIPV') has submitted_at (2024-11-17 13:26:27) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 212 ('418 Cedar Rd - 11kW ST') has submitted_at (2024-12-05 11:27:17) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 213 ('6614 Elm St - 12kW BIPV') has submitted_at (2024-10-27 11:29:57) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 214 ('8759 Elm St - 5kW BIPV') has submitted_at (2024-08-17 20:42:15) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 215 ('5320 Oak Ave - 6kW PV') has submitted_at (2024-08-22 21:17:03) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 216 ('9118 Maple Dr - 12kW ST') has submitted_at (2024-02-05 23:23:44) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 219 ('1444 Washington Blvd - 9kW ST') has submitted_at (2024-09-12 08:32:19) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 221 ('7006 Washington Blvd - 10kW ST') has submitted_at (2024-06-21 21:52:55) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 223 ('7530 Maple Dr - 13kW PV') has submitted_at (2024-08-05 10:35:13) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 224 ('5333 Cedar Rd - 14kW ST') has submitted_at (2024-08-18 00:01:21) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 225 ('260 Cedar Rd - 12kW PV+ST') has submitted_at (2024-09-08 09:48:11) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 226 ('8399 Pine Ln - 13kW PV+ST') has submitted_at (2025-01-17 07:22:38) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 227 ('3896 Elm St - 8kW PV') has submitted_at (2024-07-25 11:44:16) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 229 ('6199 Oak Ave - 12kW PV') has submitted_at (2024-05-25 05:35:21) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 230 ('9715 Elm St - 13kW ST') has submitted_at (2024-04-23 08:59:30) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 231 ('9481 Pine Ln - 10kW ST') has submitted_at (2024-08-26 10:50:20) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 232 ('1399 Pine Ln - 10kW ST') has submitted_at (2024-11-25 20:11:39) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 233 ('8185 Main St - 14kW BIPV') has submitted_at (2024-12-29 22:24:57) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 234 ('2763 Pine Ln - 3kW ST') has submitted_at (2024-04-15 21:56:53) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 235 ('7253 Maple Dr - 12kW PV') has submitted_at (2024-06-26 19:01:34) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 237 ('3006 Main St - 8kW PV+ST') has submitted_at (2024-02-04 18:47:37) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 238 ('975 Washington Blvd - 6kW PV') has submitted_at (2024-03-29 02:16:22) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 239 ('2199 Washington Blvd - 10kW PV+ST') has submitted_at (2024-12-22 06:59:20) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 240 ('2231 Main St - 11kW PV') has submitted_at (2024-02-29 09:50:28) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 241 ('9380 Washington Blvd - 12kW PV') has submitted_at (2024-08-28 15:17:22) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 242 ('1130 Oak Ave - 7kW ST') has submitted_at (2024-12-16 23:42:41) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 243 ('156 Washington Blvd - 14kW BIPV') has submitted_at (2024-03-14 23:00:59) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 244 ('8772 Elm St - 14kW ST') has submitted_at (2024-04-15 21:59:54) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 245 ('2435 Oak Ave - 12kW PV') has submitted_at (2024-01-25 05:24:05) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 246 ('4719 Elm St - 9kW BIPV') has submitted_at (2024-02-26 12:53:03) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 247 ('1465 Washington Blvd - 6kW BIPV') has submitted_at (2024-01-08 13:26:12) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 248 ('8265 Cedar Rd - 4kW PV') has submitted_at (2024-03-08 18:40:51) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 249 ('7335 Oak Ave - 14kW PV+ST') has submitted_at (2024-03-20 07:20:57) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 251 ('400 Elm St - 9kW PV') has submitted_at (2024-06-15 14:30:00) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 252 ('812 Pine Rd - 6kW PV') has submitted_at (2024-07-01 09:00:00) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 254 ('2200 Oak Ave - 8kW PV') has submitted_at (2024-09-01 08:00:00) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 255 ('3010 Maple Dr - 15kW PV+ST') has submitted_at (2024-11-20 16:45:00) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 257 ('999 Cedar Rd - 10kW BIPV') has submitted_at (2024-04-10 11:00:00) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 301 ('100 Sunridge Blvd - 20kW PV') has submitted_at (2024-08-01 10:00:00) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 302 ('250 Valley Dr - 12kW ST') has submitted_at (2024-10-15 08:30:00) before created_at (2026-03-14 16:24:40)
- **Impossible Timestamp**: Project ID 303 ('75 Ridgeview Ct - 8kW PV') has submitted_at (2024-09-20 12:00:00) before created_at (2026-03-14 16:24:40)
- **Missing Required Data**: Project ID 256 has missing or empty title

### Warning
_Issues that indicate bad data but won't crash the app_

- **Status/Timestamp Inconsistency**: Project ID 252 ('812 Pine Rd - 6kW PV') has status 'approved' but missing approved_at timestamp
- **Unreasonably Fast Approval**: Project ID 257 ('999 Cedar Rd - 10kW BIPV') was approved 2 seconds after submission
- **Invalid Value**: Project ID 118 ('2286 Oak Ave - 4kW BIPV') has unrecognized status 'paid'
- **Invalid Value**: Project ID 121 ('8789 Elm St - 6kW PV+ST') has unrecognized status 'paid'
- **Invalid Value**: Project ID 131 ('3633 Maple Dr - 15kW PV') has unrecognized status 'paid'
- **Invalid Value**: Project ID 132 ('7213 Washington Blvd - 6kW PV+ST') has unrecognized status 'paid'
- **Invalid Value**: Project ID 138 ('5379 Maple Dr - 12kW ST') has unrecognized status 'paid'
- **Invalid Value**: Project ID 153 ('1221 Main St - 8kW PV') has unrecognized status 'paid'
- **Invalid Value**: Project ID 154 ('4394 Cedar Rd - 5kW PV') has unrecognized status 'paid'
- **Invalid Value**: Project ID 163 ('8552 Elm St - 3kW PV+ST') has unrecognized status 'paid'
- **Invalid Value**: Project ID 174 ('6502 Oak Ave - 15kW PV') has unrecognized status 'paid'
- **Invalid Value**: Project ID 176 ('4054 Maple Dr - 8kW PV') has unrecognized status 'paid'
- **Invalid Value**: Project ID 177 ('490 Elm St - 12kW BIPV') has unrecognized status 'paid'
- **Invalid Value**: Project ID 191 ('6457 Elm St - 13kW ST') has unrecognized status 'paid'
- **Invalid Value**: Project ID 192 ('9952 Main St - 14kW PV') has unrecognized status 'paid'
- **Invalid Value**: Project ID 193 ('7244 Elm St - 13kW PV') has unrecognized status 'paid'
- **Invalid Value**: Project ID 205 ('289 Main St - 10kW PV+ST') has unrecognized status 'paid'
- **Invalid Value**: Project ID 207 ('6500 Main St - 13kW BIPV') has unrecognized status 'paid'
- **Invalid Value**: Project ID 208 ('3949 Elm St - 14kW PV+ST') has unrecognized status 'paid'
- **Invalid Value**: Project ID 211 ('8092 Cedar Rd - 12kW BIPV') has unrecognized status 'paid'
- **Invalid Value**: Project ID 212 ('418 Cedar Rd - 11kW ST') has unrecognized status 'paid'
- **Invalid Value**: Project ID 215 ('5320 Oak Ave - 6kW PV') has unrecognized status 'paid'
- **Invalid Value**: Project ID 216 ('9118 Maple Dr - 12kW ST') has unrecognized status 'paid'
- **Invalid Value**: Project ID 225 ('260 Cedar Rd - 12kW PV+ST') has unrecognized status 'paid'
- **Invalid Value**: Project ID 226 ('8399 Pine Ln - 13kW PV+ST') has unrecognized status 'paid'
- **Invalid Value**: Project ID 227 ('3896 Elm St - 8kW PV') has unrecognized status 'paid'
- **Invalid Value**: Project ID 229 ('6199 Oak Ave - 12kW PV') has unrecognized status 'paid'
- **Invalid Value**: Project ID 234 ('2763 Pine Ln - 3kW ST') has unrecognized status 'paid'
- **Invalid Value**: Project ID 238 ('975 Washington Blvd - 6kW PV') has unrecognized status 'paid'
- **Invalid Value**: Project ID 239 ('2199 Washington Blvd - 10kW PV+ST') has unrecognized status 'paid'
- **Invalid Value**: Project ID 241 ('9380 Washington Blvd - 12kW PV') has unrecognized status 'paid'

### Info
_Minor anomalies or observations worth noting_

None found.

## Recommendations

### Critical Issues
- **Orphaned References**: Fix foreign key constraints or clean up orphaned records
- **Missing Required Data**: Add application-level validation to prevent empty titles and statuses
- **Future Timestamps**: Investigate data entry process and add client-side validation
- **Impossible Timestamps**: Review business logic for status transitions

### Warning Issues
- **Status/Timestamp Inconsistencies**: Implement validation rules in the backend to enforce proper status/timestamp relationships
- **Unreasonably Fast Approvals**: Consider adding minimum review time validation or flagging for manual review

### Security & Performance Considerations
- Add input validation to prevent injection attacks through form fields
- Consider indexing frequently queried timestamp columns for better performance
- Implement row-level security for multi-tenant data isolation
- Use chunked queries for large dataset scans to prevent memory issues
