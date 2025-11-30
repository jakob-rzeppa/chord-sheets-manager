import { defineConfig } from 'vitepress'

// https://vitepress.dev/reference/site-config
export default defineConfig({
  title: "Chord Sheets Manager",
  description: "A management tool for guitar chord sheets.",
  themeConfig: {
    // https://vitepress.dev/reference/default-theme-config
    nav: [
      { text: 'Home', link: '/' },
      { text: 'Guide Getting-Started', link: '/guide/getting-started' },
      { text: 'Docker Deployment', link: '/guide/docker-deployment' },
      { text: 'Api Overview', link: '/api/overview' }
    ],

    sidebar: [
      {
        text: 'Examples',
        items: [
          { text: 'Guide Getting-Started', link: '/guide/getting-started' },
          { text: 'Docker Deployment', link: '/guide/docker-deployment' },
          { text: 'Api Overview', link: '/api/overview' },
          { text: 'Sheets Api', link: '/api/sheet' },
          { text: 'Artists Api', link: '/api/artist' },
          { text: 'Tags Api', link: '/api/tag' },
        ]
      }
    ],

    socialLinks: [
      { icon: 'github', link: 'https://github.com/jakob-rzeppa/chord-sheets-manager' }
    ]
  }
})
